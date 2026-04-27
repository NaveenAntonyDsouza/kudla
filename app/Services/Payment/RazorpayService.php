<?php

namespace App\Services\Payment;

use App\Models\Subscription;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay implementation of PaymentGatewayInterface.
 *
 * API: https://api.razorpay.com/v1/orders (HTTP basic auth: key_id:key_secret)
 * Signature scheme: hmac_sha256(order_id + "|" + payment_id, key_secret)
 *
 * Configuration source (in priority order):
 *   1. SiteSetting overrides (set by admin via Filament — handled
 *      transparently by App\Providers\GatewayConfigProvider which
 *      pushes them into config('services.razorpay.*') at boot).
 *   2. Env vars RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET / RAZORPAY_WEBHOOK_SECRET.
 *
 * isConfigured() returns true only when both key_id and key_secret are
 * present. Webhook secret is checked separately by the webhook handler
 * in step-5.
 *
 * Mirrors the existing web flow in
 * App\Http\Controllers\MembershipController so web and API behave
 * identically. The actual HTTP call + signature math are identical;
 * only the request/response packaging differs.
 *
 * Razorpay-specific persistence: uses dedicated columns
 * (razorpay_order_id, razorpay_payment_id, razorpay_signature) on the
 * Subscription model — preserved for backwards compatibility with the
 * existing web flow. Future gateways store IDs in the JSON
 * `gateway_metadata` column.
 */
class RazorpayService implements PaymentGatewayInterface
{
    public function getSlug(): string
    {
        return 'razorpay';
    }

    public function getName(): string
    {
        return 'Razorpay';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->keyId()) && ! empty($this->keySecret());
    }

    /**
     * Creates an order on Razorpay's API and returns a payload Flutter
     * can pass directly into the Razorpay client SDK.
     *
     * @throws \RuntimeException when the Razorpay API call fails or
     *                           returns no order id.
     */
    public function createOrder(int $amountInPaise, array $metadata = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Razorpay is not configured (key_id / key_secret missing).');
        }

        $response = Http::withoutVerifying()
            ->withBasicAuth($this->keyId(), $this->keySecret())
            ->acceptJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => $metadata['receipt'] ?? ('rcpt_'.uniqid()),
                'notes' => $this->buildNotes($metadata),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Razorpay order creation failed: '.$response->status().' '.$response->body(),
            );
        }

        $order = $response->json();
        if (empty($order['id'])) {
            throw new \RuntimeException('Razorpay returned no order id.');
        }

        // Shape is what Flutter needs to invoke Razorpay's client SDK.
        return [
            'order_id' => (string) $order['id'],
            'key_id' => (string) $this->keyId(),  // public key — safe to expose
            'amount' => (int) ($order['amount'] ?? $amountInPaise),
            'currency' => (string) ($order['currency'] ?? 'INR'),
            'status' => (string) ($order['status'] ?? 'created'),
        ];
    }

    /**
     * Razorpay signature verification:
     *   expected = hmac_sha256(order_id + "|" + payment_id, key_secret)
     *   valid   <=> expected === provided_signature
     *
     * Pure math — no network call. hash_equals used to defend against
     * timing attacks on the comparison.
     *
     * Anti-substitution: the supplied `razorpay_order_id` MUST match the
     * order id we stored on this subscription during createOrder, otherwise
     * a user with two pending subs could pay one and replay the IDs against
     * the other. (Phase 2a security audit, Vuln 1.)
     */
    public function verifyPayment(array $data, Subscription $subscription): bool
    {
        $orderId = (string) ($data['razorpay_order_id'] ?? '');
        $paymentId = (string) ($data['razorpay_payment_id'] ?? '');
        $signature = (string) ($data['razorpay_signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '' || ! $this->isConfigured()) {
            return false;
        }

        // Bind to this subscription. hash_equals to keep the comparison
        // timing-safe (overkill here but keeps the invariant uniform with
        // the signature check below).
        $expectedOrderId = (string) ($subscription->razorpay_order_id ?? '');
        if ($expectedOrderId === '' || ! hash_equals($expectedOrderId, $orderId)) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->keySecret());

        return hash_equals($expected, $signature);
    }

    public function verifyValidationRules(): array
    {
        return [
            'razorpay_order_id' => 'required|string|max:100',
            'razorpay_payment_id' => 'required|string|max:100',
            'razorpay_signature' => 'required|string|max:200',
        ];
    }

    public function applyOrderIdsToSubscription(Subscription $subscription, array $orderResponse): void
    {
        $subscription->update([
            'razorpay_order_id' => (string) ($orderResponse['order_id'] ?? ''),
        ]);
    }

    public function applyVerifiedIdsToSubscription(Subscription $subscription, array $verifyData): void
    {
        $subscription->update([
            'razorpay_payment_id' => (string) ($verifyData['razorpay_payment_id'] ?? ''),
            'razorpay_signature' => (string) ($verifyData['razorpay_signature'] ?? ''),
        ]);
    }

    /* ==================================================================
     |  Webhook (week 4 step 5)
     | ================================================================== */

    /**
     * Handle an inbound Razorpay webhook.
     *
     * Razorpay signs the request with the WEBHOOK secret (not the API
     * secret) — sent in the `X-Razorpay-Signature` header as
     * hmac_sha256(raw_body, webhook_secret). We compare with hash_equals
     * (timing-safe).
     *
     * Dedupe: the gateway+event_id pair has a unique index on
     * webhook_events. Duplicate inserts throw QueryException — caught
     * and rendered as 200 OK without re-processing.
     *
     * Events we act on:
     *   - payment.captured  → SubscriptionActivator::activate
     *   - payment.failed    → SubscriptionActivator::markFailed
     *   - refund.processed  → SubscriptionActivator::markRefunded
     * Anything else: stored as 'ignored', return 200 (we tolerate
     * unknown events so Razorpay's dashboard sees them as received).
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $webhookSecret = $this->webhookSecret();

        // Misconfiguration on our side — return 500-ish but as 503 so
        // Razorpay retries (they will, and by then we hope an admin
        // populated the secret).
        if (empty($webhookSecret)) {
            Log::warning('Razorpay webhook received but webhook_secret is not configured.');

            return response()->json(['error' => 'Webhook secret not configured.'], 503);
        }

        // Signature check — return 401 so Razorpay marks the event
        // failed in their dashboard. Don't return 200 (would mask a
        // real config drift / attack).
        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        if ($signature === '' || ! hash_equals($expected, $signature)) {
            Log::warning('Razorpay webhook signature mismatch.', [
                'ip' => $request->ip(),
                'has_signature_header' => $signature !== '',
            ]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $event = json_decode($rawBody, true);
        if (! is_array($event)) {
            return response()->json(['error' => 'Malformed JSON.'], 422);
        }

        $eventId = (string) ($event['id'] ?? $event['payload']['payment']['entity']['id'] ?? '');
        $eventType = (string) ($event['event'] ?? '');
        if ($eventId === '' || $eventType === '') {
            return response()->json(['error' => 'Missing event id or type.'], 422);
        }

        // Dedupe — atomic via unique index on (gateway, event_id).
        try {
            WebhookEvent::create([
                'gateway' => $this->getSlug(),
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => WebhookEvent::STATUS_PROCESSED,  // optimistic; reset to ignored/failed below
                'payload' => $event,
            ]);
        } catch (\Throwable $e) {
            // Unique violation → seen before. Same response Razorpay's
            // first-attempt path returned, so they stop retrying.
            return response()->json(['status' => 'duplicate'], 200);
        }

        $activator = app(SubscriptionActivator::class);

        $outcome = match ($eventType) {
            'payment.captured' => $this->dispatchPaymentCaptured($event, $activator),
            'payment.failed' => $this->dispatchPaymentFailed($event, $activator),
            'refund.processed' => $this->dispatchRefundProcessed($event, $activator),
            default => 'ignored',
        };

        if ($outcome === 'ignored') {
            // Mark the row 'ignored' so admins can see at a glance
            // which event types we don't handle.
            WebhookEvent::where('gateway', $this->getSlug())
                ->where('event_id', $eventId)
                ->update(['status' => WebhookEvent::STATUS_IGNORED]);
        }

        return response()->json(['status' => $outcome], 200);
    }

    private function dispatchPaymentCaptured(array $event, SubscriptionActivator $activator): string
    {
        $orderId = (string) ($event['payload']['payment']['entity']['order_id'] ?? '');
        if ($orderId === '') {
            return 'ignored';
        }

        $subscription = Subscription::where('razorpay_order_id', $orderId)->first();
        if (! $subscription) {
            return 'ignored';
        }

        // Persist the verified payment_id from the webhook event (in case
        // the synchronous /verify call never fired or fired without it).
        $paymentId = (string) ($event['payload']['payment']['entity']['id'] ?? '');
        if ($paymentId !== '' && empty($subscription->razorpay_payment_id)) {
            $subscription->update(['razorpay_payment_id' => $paymentId]);
        }

        $activator->activate($subscription);

        return 'processed';
    }

    private function dispatchPaymentFailed(array $event, SubscriptionActivator $activator): string
    {
        $orderId = (string) ($event['payload']['payment']['entity']['order_id'] ?? '');
        if ($orderId === '') {
            return 'ignored';
        }

        $subscription = Subscription::where('razorpay_order_id', $orderId)->first();
        if (! $subscription) {
            return 'ignored';
        }

        $activator->markFailed($subscription);

        return 'processed';
    }

    private function dispatchRefundProcessed(array $event, SubscriptionActivator $activator): string
    {
        $paymentId = (string) ($event['payload']['refund']['entity']['payment_id'] ?? '');
        if ($paymentId === '') {
            return 'ignored';
        }

        $subscription = Subscription::where('razorpay_payment_id', $paymentId)->first();
        if (! $subscription) {
            return 'ignored';
        }

        $activator->markRefunded($subscription);

        return 'processed';
    }

    /* ==================================================================
     |  Internals
     | ================================================================== */

    private function keyId(): ?string
    {
        return config('services.razorpay.key');
    }

    private function keySecret(): ?string
    {
        return config('services.razorpay.secret');
    }

    /**
     * Webhook signing secret — DIFFERENT from the API secret.
     * Razorpay generates this when you create a webhook in their
     * dashboard; we sync it via SiteSetting → config in
     * GatewayConfigProvider.
     */
    private function webhookSecret(): ?string
    {
        return config('services.razorpay.webhook_secret');
    }

    /**
     * Build the `notes` object Razorpay echoes back on the order. Used
     * by support / reconciliation. Keys are user-defined. Razorpay
     * limits: 15 keys max, 256 chars each. We pass a small subset only.
     */
    private function buildNotes(array $metadata): array
    {
        $notes = [];
        foreach (['user_id', 'plan_id', 'plan_name', 'coupon_code', 'subscription_id'] as $key) {
            if (isset($metadata[$key])) {
                $notes[$key] = (string) $metadata[$key];
            }
        }

        return $notes;
    }
}
