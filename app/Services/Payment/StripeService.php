<?php

namespace App\Services\Payment;

use App\Models\Subscription;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe implementation of PaymentGatewayInterface.
 *
 * API: https://api.stripe.com/v1/payment_intents (HTTP basic auth: secret_key:)
 *      Body is form-encoded (NOT JSON). Stripe rejects JSON.
 *
 * Signature scheme (webhook): Stripe-Signature header
 *   Format: t={ts},v1={sig1},v1={sig2}     (multiple v1 entries during secret rotation)
 *   sig    = hmac_sha256(t + "." + raw_body, webhook_secret)
 *   tolerance: 300s — older timestamps are rejected to prevent replay attacks.
 *
 * Configuration source (in priority order):
 *   1. SiteSetting overrides (set by admin via Filament — handled
 *      transparently by App\Providers\GatewayConfigProvider which
 *      pushes them into config('services.stripe.*') at boot).
 *   2. Env vars STRIPE_KEY / STRIPE_SECRET / STRIPE_WEBHOOK_SECRET.
 *
 * isConfigured() returns true only when secret is present (the
 * publishable key is optional server-side; we only need it to ship
 * to Flutter via the order response).
 *
 * Persistence model: gateway_metadata JSON (no dedicated columns).
 *   After createOrder:  metadata.payment_intent_id
 *   After verifyPayment: metadata.charge_id (best-effort, may be null
 *                        if Stripe hasn't finalised the charge yet —
 *                        webhook will fill it in).
 *
 * Webhook lookup: matches Subscription by gateway='stripe' and
 *   gateway_metadata->payment_intent_id = {intent_id}. Laravel's JSON
 *   path syntax compiles to JSON_EXTRACT on both MySQL 5.7+ and SQLite
 *   (with JSON1) so the same query works in tests + production.
 */
class StripeService implements PaymentGatewayInterface
{
    /** Stripe webhook signature tolerance — matches the official SDK default. */
    private const SIGNATURE_TOLERANCE_SECONDS = 300;

    public function getSlug(): string
    {
        return 'stripe';
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secret());
    }

    /**
     * Creates a PaymentIntent on Stripe's API and returns a payload Flutter
     * can pass into the Stripe SDK (uses client_secret + publishable key).
     *
     * @throws \RuntimeException when the Stripe API call fails or
     *                           returns no intent id.
     */
    public function createOrder(int $amountInPaise, array $metadata = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Stripe is not configured (secret missing).');
        }

        // Build form params. Stripe requires array notation for nested
        // metadata: metadata[user_id]=7. asForm() handles the encoding.
        $body = [
            'amount' => $amountInPaise,
            'currency' => 'inr',  // INR for matrimony — see config note in class doc.
            'payment_method_types' => ['card'],
            'metadata' => $this->buildMetadata($metadata),
        ];

        $response = Http::withoutVerifying()
            ->withBasicAuth($this->secret(), '')
            ->asForm()
            ->acceptJson()
            ->post('https://api.stripe.com/v1/payment_intents', $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Stripe PaymentIntent creation failed: '.$response->status().' '.$response->body(),
            );
        }

        $intent = $response->json();
        if (empty($intent['id']) || empty($intent['client_secret'])) {
            throw new \RuntimeException('Stripe returned no intent id / client_secret.');
        }

        return [
            'payment_intent_id' => (string) $intent['id'],
            'client_secret' => (string) $intent['client_secret'],
            'publishable_key' => (string) ($this->publishableKey() ?? ''),
            'amount' => (int) ($intent['amount'] ?? $amountInPaise),
            'currency' => (string) ($intent['currency'] ?? 'inr'),
            'status' => (string) ($intent['status'] ?? 'requires_payment_method'),
        ];
    }

    /**
     * Verify a Stripe payment by fetching the PaymentIntent server-side
     * and checking status === 'succeeded'. The client-side SDK already
     * confirms via 3DS / Apple Pay / etc., but a server-side fetch is
     * the only way to verify the payment is genuinely captured (vs.
     * just confirmed by the client).
     */
    public function verifyPayment(array $data): bool
    {
        $intentId = (string) ($data['payment_intent_id'] ?? '');
        if ($intentId === '' || ! $this->isConfigured()) {
            return false;
        }

        $response = Http::withoutVerifying()
            ->withBasicAuth($this->secret(), '')
            ->acceptJson()
            ->get("https://api.stripe.com/v1/payment_intents/{$intentId}");

        if (! $response->successful()) {
            return false;
        }

        $intent = $response->json();

        return ($intent['status'] ?? null) === 'succeeded';
    }

    public function verifyValidationRules(): array
    {
        return [
            'payment_intent_id' => 'required|string|max:200',
        ];
    }

    public function applyOrderIdsToSubscription(Subscription $subscription, array $orderResponse): void
    {
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'payment_intent_id' => (string) ($orderResponse['payment_intent_id'] ?? ''),
            ]),
        ]);
    }

    public function applyVerifiedIdsToSubscription(Subscription $subscription, array $verifyData): void
    {
        // Stripe's verify path only carries the payment_intent_id —
        // charge_id arrives on the webhook (charge.refunded /
        // payment_intent.succeeded events). We persist the intent id
        // again here for safety in case applyOrderIdsToSubscription
        // didn't fire (e.g. retry path).
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'payment_intent_id' => (string) ($verifyData['payment_intent_id'] ?? ($existing['payment_intent_id'] ?? '')),
            ]),
        ]);
    }

    /* ==================================================================
     |  Webhook
     | ================================================================== */

    /**
     * Handle an inbound Stripe webhook.
     *
     * Stripe-Signature header is parsed for t (timestamp) and v1
     * (HMAC-SHA256 signatures — multiple during rotation). We compute
     * the expected signature with each known webhook secret and compare
     * via hash_equals. Timestamp must be within 300s of now to defend
     * against replay attacks.
     *
     * Dedupe: gateway+event_id pair has a unique index on webhook_events.
     * Duplicate inserts throw QueryException → caught and rendered as
     * 200 OK without re-processing.
     *
     * Events we act on:
     *   - payment_intent.succeeded       → SubscriptionActivator::activate
     *   - payment_intent.payment_failed  → SubscriptionActivator::markFailed
     *   - payment_intent.canceled        → SubscriptionActivator::markFailed
     *   - charge.refunded                → SubscriptionActivator::markRefunded
     * Anything else: stored as 'ignored', return 200.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signatureHeader = (string) $request->header('Stripe-Signature', '');
        $webhookSecret = $this->webhookSecret();

        if (empty($webhookSecret)) {
            Log::warning('Stripe webhook received but webhook_secret is not configured.');

            return response()->json(['error' => 'Webhook secret not configured.'], 503);
        }

        if (! $this->verifyStripeSignature($rawBody, $signatureHeader, $webhookSecret)) {
            Log::warning('Stripe webhook signature mismatch.', [
                'ip' => $request->ip(),
                'has_signature_header' => $signatureHeader !== '',
            ]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $event = json_decode($rawBody, true);
        if (! is_array($event)) {
            return response()->json(['error' => 'Malformed JSON.'], 422);
        }

        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? '');
        if ($eventId === '' || $eventType === '') {
            return response()->json(['error' => 'Missing event id or type.'], 422);
        }

        // Dedupe — atomic via unique index on (gateway, event_id).
        try {
            WebhookEvent::create([
                'gateway' => $this->getSlug(),
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => WebhookEvent::STATUS_PROCESSED,
                'payload' => $event,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        $activator = app(SubscriptionActivator::class);

        $outcome = match ($eventType) {
            'payment_intent.succeeded' => $this->dispatchIntentSucceeded($event, $activator),
            'payment_intent.payment_failed', 'payment_intent.canceled' => $this->dispatchIntentFailed($event, $activator),
            'charge.refunded' => $this->dispatchChargeRefunded($event, $activator),
            default => 'ignored',
        };

        if ($outcome === 'ignored') {
            WebhookEvent::where('gateway', $this->getSlug())
                ->where('event_id', $eventId)
                ->update(['status' => WebhookEvent::STATUS_IGNORED]);
        }

        return response()->json(['status' => $outcome], 200);
    }

    /**
     * Verify a Stripe-Signature header.
     *
     * Header format: t=1492774577,v1=hex-sig,v1=hex-sig
     * Algorithm:     sig = hmac_sha256(t + "." + raw_body, webhook_secret)
     */
    private function verifyStripeSignature(string $rawBody, string $signatureHeader, string $webhookSecret): bool
    {
        if ($signatureHeader === '') {
            return false;
        }

        // Parse "t=...,v1=...,v1=..." into timestamp + list of v1 sigs.
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) {
                continue;
            }
            [$key, $value] = $kv;
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || empty($signatures)) {
            return false;
        }

        // Replay protection — reject events older than the tolerance window.
        if (abs(time() - $timestamp) > self::SIGNATURE_TOLERANCE_SECONDS) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $webhookSecret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function dispatchIntentSucceeded(array $event, SubscriptionActivator $activator): string
    {
        $intent = $event['data']['object'] ?? [];
        $intentId = (string) ($intent['id'] ?? '');
        if ($intentId === '') {
            return 'ignored';
        }

        $subscription = $this->findSubscriptionByIntentId($intentId);
        if (! $subscription) {
            return 'ignored';
        }

        // Persist the charge_id from the webhook event for refund tracking.
        // Stripe API returns charge id in `latest_charge` (current API) or
        // `charges.data[0].id` (older API). Defensive about both shapes.
        $chargeId = (string) ($intent['latest_charge'] ?? ($intent['charges']['data'][0]['id'] ?? ''));
        $existing = $subscription->gateway_metadata ?? [];
        $merged = array_merge((array) $existing, ['payment_intent_id' => $intentId]);
        if ($chargeId !== '') {
            $merged['charge_id'] = $chargeId;
        }
        $subscription->update(['gateway_metadata' => $merged]);

        $activator->activate($subscription);

        return 'processed';
    }

    private function dispatchIntentFailed(array $event, SubscriptionActivator $activator): string
    {
        $intent = $event['data']['object'] ?? [];
        $intentId = (string) ($intent['id'] ?? '');
        if ($intentId === '') {
            return 'ignored';
        }

        $subscription = $this->findSubscriptionByIntentId($intentId);
        if (! $subscription) {
            return 'ignored';
        }

        $activator->markFailed($subscription);

        return 'processed';
    }

    private function dispatchChargeRefunded(array $event, SubscriptionActivator $activator): string
    {
        $charge = $event['data']['object'] ?? [];
        // charge.refunded events carry the charge as the object; the
        // payment_intent reference is on the same object.
        $intentId = (string) ($charge['payment_intent'] ?? '');
        if ($intentId === '') {
            return 'ignored';
        }

        $subscription = $this->findSubscriptionByIntentId($intentId);
        if (! $subscription) {
            return 'ignored';
        }

        $activator->markRefunded($subscription);

        return 'processed';
    }

    /**
     * Lookup a Stripe subscription by payment_intent_id stored in
     * gateway_metadata JSON. Laravel's `column->path` syntax compiles to
     * JSON_EXTRACT on both MySQL and SQLite.
     */
    private function findSubscriptionByIntentId(string $intentId): ?Subscription
    {
        return Subscription::where('gateway', $this->getSlug())
            ->where('gateway_metadata->payment_intent_id', $intentId)
            ->first();
    }

    /* ==================================================================
     |  Internals
     | ================================================================== */

    private function publishableKey(): ?string
    {
        return config('services.stripe.key');
    }

    private function secret(): ?string
    {
        return config('services.stripe.secret');
    }

    private function webhookSecret(): ?string
    {
        return config('services.stripe.webhook_secret');
    }

    /**
     * Build the metadata Stripe stores on the PaymentIntent. Limits:
     * 50 keys max, 500 chars per value. We pass a small subset only.
     */
    private function buildMetadata(array $metadata): array
    {
        $out = [];
        foreach (['user_id', 'plan_id', 'plan_name', 'coupon_code', 'subscription_id'] as $key) {
            if (isset($metadata[$key])) {
                $out[$key] = (string) $metadata[$key];
            }
        }

        return $out;
    }
}
