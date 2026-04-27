<?php

namespace App\Services\Payment;

use App\Models\Subscription;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal implementation of PaymentGatewayInterface.
 *
 * API: https://api-m.paypal.com (live) | https://api-m.sandbox.paypal.com (sandbox)
 *
 * Auth model is OAuth2 client_credentials, NOT HTTP Basic on every call:
 *   1. Once: POST /v1/oauth2/token with Basic(client_id:secret) →
 *      access_token (TTL ~9h).
 *   2. All subsequent API calls: Authorization: Bearer {access_token}.
 *   The token is cached so we don't OAuth on every call.
 *
 * Order/capture flow (intent: CAPTURE):
 *   /order  → POST /v2/checkout/orders        (status: CREATED)
 *   client  → buyer approves via PayPal SDK   (status: APPROVED)
 *   /verify → POST /v2/checkout/orders/{id}/capture  (status: COMPLETED)
 *
 * Webhook signature verification: PayPal does NOT use a shared HMAC
 * secret. Inbound webhooks carry RSA-signed transmission headers
 * (Paypal-Auth-Algo, Paypal-Cert-Url, Paypal-Transmission-{Id,Sig,Time})
 * that verify against PayPal's cert chain. We chose the simpler-and-
 * safer path: call PayPal's POST /v1/notifications/verify-webhook-signature
 * and trust their answer. One HTTP call per webhook is fine — webhooks
 * are async and rare relative to normal traffic.
 *
 * Currency: PayPal-India merchant accounts cannot receive INR. Default
 * to USD; buyer can override per their target market via SiteSetting.
 *
 * Persistence model: gateway_metadata JSON.
 *   After createOrder:    {paypal_order_id, paypal_status: 'CREATED'}
 *   After verifyPayment:  + {paypal_capture_id, paypal_status: 'COMPLETED'}
 *
 * Subscription lookup in webhooks: PayPal echoes our `custom_id` (set
 * to subscription.id when creating the order) in the capture event's
 * resource. We match on that for the most reliable lookup.
 */
class PayPalService implements PaymentGatewayInterface
{
    /** OAuth token cache TTL (minutes). PayPal tokens last 9h; 540min leaves margin. */
    private const TOKEN_CACHE_TTL_MINUTES = 540;

    public function getSlug(): string
    {
        return 'paypal';
    }

    public function getName(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->clientId()) && ! empty($this->secret());
    }

    /**
     * Create a PayPal order and return a Flutter-friendly payload. The
     * client SDK uses paypal_order_id to launch the approval flow; the
     * approve_url is a fallback for browser-based redirect approval.
     *
     * @throws \RuntimeException when the PayPal API call fails or returns
     *                           no order id.
     */
    public function createOrder(int $amountInPaise, array $metadata = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('PayPal is not configured (client_id / secret missing).');
        }

        $currency = strtoupper((string) config('services.paypal.currency', 'USD'));
        $amount = $this->formatAmount($amountInPaise);

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
                'custom_id' => (string) ($metadata['subscription_id'] ?? ''),
                'invoice_id' => (string) ($metadata['receipt'] ?? 'inv_'.uniqid()),
                'description' => (string) ($metadata['plan_name'] ?? 'Membership'),
            ]],
        ];

        $response = $this->authenticatedRequest()
            ->post($this->apiBase().'/v2/checkout/orders', $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'PayPal order creation failed: '.$response->status().' '.$response->body(),
            );
        }

        $order = $response->json();
        if (empty($order['id'])) {
            throw new \RuntimeException('PayPal returned no order id.');
        }

        return [
            'paypal_order_id' => (string) $order['id'],
            'status' => (string) ($order['status'] ?? 'CREATED'),
            'approve_url' => $this->extractApproveUrl($order['links'] ?? []),
            'currency' => $currency,
            'amount' => $amount,
            'client_id' => (string) $this->clientId(),  // public — safe for Flutter
            'mode' => $this->mode(),
        ];
    }

    /**
     * Verify a PayPal payment by capturing the order server-side. With
     * intent=CAPTURE, calling /capture moves funds and returns COMPLETED.
     * Idempotent against repeat-call: PayPal returns 422 ORDER_ALREADY_CAPTURED
     * on the second hit; we fall through to GET /orders/{id} and check
     * the order status.
     */
    public function verifyPayment(array $data, Subscription $subscription): bool
    {
        $orderId = (string) ($data['paypal_order_id'] ?? '');
        if ($orderId === '' || ! $this->isConfigured()) {
            return false;
        }

        // Anti-substitution: bind to the order id we stored on this
        // subscription during createOrder. (Phase 2a security audit, Vuln 1.)
        $persisted = (string) (($subscription->gateway_metadata['paypal_order_id'] ?? null));
        if ($persisted === '' || ! hash_equals($persisted, $orderId)) {
            return false;
        }

        $captureResponse = $this->authenticatedRequest()
            ->post($this->apiBase()."/v2/checkout/orders/{$orderId}/capture");

        if ($captureResponse->successful()) {
            return ($captureResponse->json('status') ?? null) === 'COMPLETED';
        }

        // 422 ORDER_ALREADY_CAPTURED — webhook beat us, or a previous
        // /verify call already captured. Fall back to a status check.
        if ($captureResponse->status() === 422) {
            $orderResponse = $this->authenticatedRequest()
                ->get($this->apiBase()."/v2/checkout/orders/{$orderId}");

            if ($orderResponse->successful()) {
                return ($orderResponse->json('status') ?? null) === 'COMPLETED';
            }
        }

        return false;
    }

    public function verifyValidationRules(): array
    {
        return [
            'paypal_order_id' => 'required|string|max:100',
        ];
    }

    public function applyOrderIdsToSubscription(Subscription $subscription, array $orderResponse): void
    {
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'paypal_order_id' => (string) ($orderResponse['paypal_order_id'] ?? ''),
                'paypal_status' => (string) ($orderResponse['status'] ?? 'CREATED'),
            ]),
        ]);
    }

    public function applyVerifiedIdsToSubscription(Subscription $subscription, array $verifyData): void
    {
        // Capture id arrives async via the PAYMENT.CAPTURE.COMPLETED webhook
        // and is persisted there. /verify carries only the order_id, so we
        // just bump the status and ensure the order_id is present.
        $existing = $subscription->gateway_metadata ?? [];
        $merged = array_merge((array) $existing, [
            'paypal_status' => 'COMPLETED',
        ]);
        if (! isset($merged['paypal_order_id']) && ! empty($verifyData['paypal_order_id'])) {
            $merged['paypal_order_id'] = (string) $verifyData['paypal_order_id'];
        }
        $subscription->update(['gateway_metadata' => $merged]);
    }

    /* ==================================================================
     |  Webhook
     | ================================================================== */

    /**
     * Handle an inbound PayPal webhook.
     *
     * Authenticity is established by calling PayPal's
     *   POST /v1/notifications/verify-webhook-signature
     * with our webhook_id + the inbound transmission headers + body.
     * Returns 401 on FAILURE, 503 if our webhook_id isn't configured.
     *
     * Dedupe: webhook_events has a unique index on (gateway, event_id).
     *
     * Events we act on:
     *   PAYMENT.CAPTURE.COMPLETED            → activate
     *   PAYMENT.CAPTURE.DENIED / .DECLINED   → markFailed
     *   PAYMENT.CAPTURE.REFUNDED             → markRefunded
     * Anything else (CHECKOUT.ORDER.APPROVED, etc.): 200 ignored.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $webhookId = $this->webhookId();

        if (empty($webhookId)) {
            Log::warning('PayPal webhook received but webhook_id is not configured.');

            return response()->json(['error' => 'Webhook id not configured.'], 503);
        }

        if (! $this->isConfigured()) {
            // We need an OAuth token to call verify-webhook-signature.
            return response()->json(['error' => 'PayPal credentials not configured.'], 503);
        }

        // Required transmission headers — missing any → 401.
        $authAlgo = (string) $request->header('Paypal-Auth-Algo', '');
        $certUrl = (string) $request->header('Paypal-Cert-Url', '');
        $transmissionId = (string) $request->header('Paypal-Transmission-Id', '');
        $transmissionSig = (string) $request->header('Paypal-Transmission-Sig', '');
        $transmissionTime = (string) $request->header('Paypal-Transmission-Time', '');
        if ($authAlgo === '' || $certUrl === '' || $transmissionId === ''
            || $transmissionSig === '' || $transmissionTime === '') {
            return response()->json(['error' => 'Missing PayPal transmission headers.'], 401);
        }

        $event = json_decode($rawBody, true);
        if (! is_array($event)) {
            return response()->json(['error' => 'Malformed JSON.'], 422);
        }

        // PayPal's verification API. webhook_event must be the parsed
        // event — they re-serialize and recompute the digest server-side.
        $verify = $this->authenticatedRequest()
            ->post($this->apiBase().'/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $authAlgo,
                'cert_url' => $certUrl,
                'transmission_id' => $transmissionId,
                'transmission_sig' => $transmissionSig,
                'transmission_time' => $transmissionTime,
                'webhook_id' => $webhookId,
                'webhook_event' => $event,
            ]);

        if (! $verify->successful() || $verify->json('verification_status') !== 'SUCCESS') {
            Log::warning('PayPal webhook signature verify FAILED.', [
                'ip' => $request->ip(),
                'transmission_id' => $transmissionId,
                'status' => $verify->status(),
                'verification' => $verify->json('verification_status'),
            ]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['event_type'] ?? '');
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
            'PAYMENT.CAPTURE.COMPLETED' => $this->dispatchCaptureCompleted($event, $activator),
            'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED' => $this->dispatchCaptureFailed($event, $activator),
            'PAYMENT.CAPTURE.REFUNDED' => $this->dispatchCaptureRefunded($event, $activator),
            default => 'ignored',
        };

        if ($outcome === 'ignored') {
            WebhookEvent::where('gateway', $this->getSlug())
                ->where('event_id', $eventId)
                ->update(['status' => WebhookEvent::STATUS_IGNORED]);
        }

        return response()->json(['status' => $outcome], 200);
    }

    private function dispatchCaptureCompleted(array $event, SubscriptionActivator $activator): string
    {
        $resource = $event['resource'] ?? [];
        $subscription = $this->findSubscription($resource);
        if (! $subscription) {
            return 'ignored';
        }

        // Persist capture id from the webhook for refund tracking.
        $captureId = (string) ($resource['id'] ?? '');
        $existing = $subscription->gateway_metadata ?? [];
        $merged = array_merge((array) $existing, ['paypal_status' => 'COMPLETED']);
        if ($captureId !== '') {
            $merged['paypal_capture_id'] = $captureId;
        }
        $subscription->update(['gateway_metadata' => $merged]);

        $activator->activate($subscription);

        return 'processed';
    }

    private function dispatchCaptureFailed(array $event, SubscriptionActivator $activator): string
    {
        $subscription = $this->findSubscription($event['resource'] ?? []);
        if (! $subscription) {
            return 'ignored';
        }

        $activator->markFailed($subscription);

        return 'processed';
    }

    private function dispatchCaptureRefunded(array $event, SubscriptionActivator $activator): string
    {
        $resource = $event['resource'] ?? [];
        // Refund events have the refund as resource; the captured payment
        // is referenced via supplementary_data.related_ids.capture_id OR
        // via the `links` rel=up which points back to the capture URL.
        $captureId = (string) ($resource['supplementary_data']['related_ids']['capture_id'] ?? '');
        if ($captureId === '') {
            // Fall back to scanning links for an `up` ref to the capture.
            foreach (($resource['links'] ?? []) as $link) {
                if (($link['rel'] ?? '') === 'up' && str_contains((string) ($link['href'] ?? ''), '/captures/')) {
                    $parts = explode('/captures/', $link['href']);
                    $captureId = end($parts) ?: '';
                    break;
                }
            }
        }

        if ($captureId === '') {
            return 'ignored';
        }

        $subscription = Subscription::where('gateway', $this->getSlug())
            ->where('gateway_metadata->paypal_capture_id', $captureId)
            ->first();
        if (! $subscription) {
            return 'ignored';
        }

        $activator->markRefunded($subscription);

        return 'processed';
    }

    /**
     * Find the matching Subscription for a webhook resource. Tries
     * custom_id first (we set it = subscription.id), then falls back
     * to paypal_order_id from supplementary_data.
     */
    private function findSubscription(array $resource): ?Subscription
    {
        $customId = (string) ($resource['custom_id'] ?? '');
        if ($customId !== '' && ctype_digit($customId)) {
            $sub = Subscription::where('gateway', $this->getSlug())
                ->where('id', (int) $customId)
                ->first();
            if ($sub) {
                return $sub;
            }
        }

        // Fallback — match by order_id stored in gateway_metadata.
        $orderId = (string) ($resource['supplementary_data']['related_ids']['order_id'] ?? '');
        if ($orderId !== '') {
            return Subscription::where('gateway', $this->getSlug())
                ->where('gateway_metadata->paypal_order_id', $orderId)
                ->first();
        }

        return null;
    }

    /* ==================================================================
     |  Internals
     | ================================================================== */

    private function clientId(): ?string
    {
        return config('services.paypal.client_id');
    }

    private function secret(): ?string
    {
        return config('services.paypal.secret');
    }

    private function webhookId(): ?string
    {
        return config('services.paypal.webhook_id');
    }

    private function mode(): string
    {
        return config('services.paypal.mode') === 'live' ? 'live' : 'sandbox';
    }

    private function apiBase(): string
    {
        return $this->mode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * OAuth2 client_credentials flow — caches the access_token across
     * requests. PayPal tokens last ~9h; we cache for slightly less.
     * On invalidation (401 on a downstream call), the caller can clear
     * the cache and retry — currently we don't need that path because
     * tests + production refresh on TTL.
     */
    private function getAccessToken(): string
    {
        return Cache::remember(
            $this->tokenCacheKey(),
            self::TOKEN_CACHE_TTL_MINUTES * 60,
            function () {
                $response = Http::withoutVerifying()
                    ->withBasicAuth($this->clientId(), $this->secret())
                    ->asForm()
                    ->acceptJson()
                    ->post($this->apiBase().'/v1/oauth2/token', [
                        'grant_type' => 'client_credentials',
                    ]);

                if (! $response->successful()) {
                    throw new \RuntimeException(
                        'PayPal OAuth token request failed: '.$response->status().' '.$response->body(),
                    );
                }

                $token = (string) $response->json('access_token', '');
                if ($token === '') {
                    throw new \RuntimeException('PayPal returned no access_token.');
                }

                return $token;
            },
        );
    }

    /**
     * Cache key per mode — sandbox and live tokens are different
     * credentials, must not collide.
     */
    private function tokenCacheKey(): string
    {
        return 'paypal_access_token_'.$this->mode();
    }

    /**
     * Pre-configured Http pending-request: bearer token + JSON content type.
     */
    private function authenticatedRequest()
    {
        return Http::withoutVerifying()
            ->withToken($this->getAccessToken())
            ->acceptJson();
    }

    /**
     * Convert paise (smallest INR unit) → currency major-unit string
     * with 2 decimals. Razorpay/Stripe accept paise; PayPal wants "9.99".
     */
    private function formatAmount(int $amountInPaise): string
    {
        return number_format($amountInPaise / 100, 2, '.', '');
    }

    /**
     * PayPal returns several HATEOAS links on a created order — we want
     * the rel=approve link (or rel=payer-action on newer flows) so the
     * client can redirect in browser fallback paths.
     */
    private function extractApproveUrl(array $links): string
    {
        foreach ($links as $link) {
            $rel = (string) ($link['rel'] ?? '');
            if ($rel === 'approve' || $rel === 'payer-action') {
                return (string) ($link['href'] ?? '');
            }
        }

        return '';
    }
}
