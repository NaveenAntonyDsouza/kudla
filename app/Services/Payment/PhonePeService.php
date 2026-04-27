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
 * PhonePe V2 Standard Checkout implementation of PaymentGatewayInterface.
 *
 * NOT to be confused with PhonePe's older V1 PG API (X-VERIFY header,
 * base64 body, salt-key checksum). V2 is the current "Standard
 * Checkout" flow with OAuth2-based auth.
 *
 * Hosts:
 *   sandbox    https://api-preprod.phonepe.com/apis/pg-sandbox
 *   production https://api.phonepe.com/apis/pg
 *   (the OAuth host on production is the identity-manager service —
 *    api.phonepe.com/apis/identity-manager — handled by oauthBase()).
 *
 * Auth: OAuth2 client_credentials → access_token → header value
 * `Authorization: O-Bearer {access_token}`. Note the literal `O-Bearer`
 * scheme name — NOT the standard `Bearer`.
 *
 * Order/verify flow:
 *   /order  → POST /checkout/v2/pay  (returns redirectUrl + PhonePe orderId)
 *   client  → buyer pays in PhonePe app / web checkout
 *   /verify → GET  /checkout/v2/order/{merchantOrderId}/status
 *             (state === 'COMPLETED' = success)
 *
 * Webhook auth is unusual:
 *   PhonePe sends `Authorization: <hex>` where hex = sha256(merchant_username:merchant_password).
 *   The username/password are configured by the merchant in the PhonePe
 *   dashboard (NOT the API client_id/client_secret). The header is the
 *   SAME on every webhook delivery — there's no per-request signature.
 *   Replay protection therefore relies entirely on our webhook_events
 *   unique-(gateway, event_id) constraint.
 *
 * Events:
 *   checkout.order.completed → activate
 *   checkout.order.failed    → markFailed
 *   pg.refund.completed      → markRefunded
 *   pg.refund.failed         → ignored (failed refund doesn't undo paid state)
 *
 * Currency: INR only (PhonePe is India-focused).
 *
 * Persistence model: gateway_metadata JSON.
 *   After createOrder:   {phonepe_merchant_order_id, phonepe_order_id, phonepe_redirect_url}
 *   After webhook (pay): + {phonepe_transaction_id, phonepe_payment_mode, phonepe_state}
 *   After webhook (refund): + {phonepe_state: 'REFUND_COMPLETED'}
 *
 * Subscription lookup: by phonepe_merchant_order_id stored in
 * gateway_metadata. We generate the merchantOrderId as
 * `phonepe_{subscription_id}_{microtime}` (PhonePe limits 63 chars,
 * underscores/hyphens only).
 */
class PhonePeService implements PaymentGatewayInterface
{
    /** Default OAuth token cache TTL in seconds when expires_at isn't usable. */
    private const TOKEN_FALLBACK_TTL_SECONDS = 1800;  // 30 minutes

    /** Safety margin subtracted from PhonePe's expires_at — prevents 401 races. */
    private const TOKEN_SAFETY_MARGIN_SECONDS = 60;

    public function getSlug(): string
    {
        return 'phonepe';
    }

    public function getName(): string
    {
        return 'PhonePe';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->clientId())
            && ! empty($this->clientSecret())
            && ! empty($this->clientVersion());
    }

    /**
     * Initiate a PhonePe Standard Checkout payment. Returns the
     * redirectUrl Flutter opens (in-app browser or PhonePe SDK).
     *
     * @throws \RuntimeException when the API call fails or returns no
     *                           redirectUrl.
     */
    public function createOrder(int $amountInPaise, array $metadata = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('PhonePe is not configured (client_id / client_secret / client_version).');
        }

        // PhonePe limits 63 chars + only [a-zA-Z0-9_-]. We use underscore-only.
        $subscriptionId = (string) ($metadata['subscription_id'] ?? '0');
        $merchantOrderId = 'phonepe_'.$subscriptionId.'_'.substr((string) (microtime(true) * 10000), -10);

        $body = [
            'merchantOrderId' => $merchantOrderId,
            'amount' => $amountInPaise,  // PhonePe accepts paise directly
            'expireAfter' => 1200,  // 20 minutes — checkout session lifetime
            'paymentFlow' => [
                'type' => 'PG_CHECKOUT',
                'merchantUrls' => [
                    // Empty when we don't drive a return URL — Flutter SDK
                    // handles the post-payment flow on its own and our
                    // webhook is the source of truth for status.
                    'redirectUrl' => '',
                ],
            ],
            'metaInfo' => $this->buildMetaInfo($metadata),
        ];

        $response = $this->authenticatedRequest()
            ->post($this->payApiBase().'/checkout/v2/pay', $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'PhonePe createOrder failed: '.$response->status().' '.$response->body(),
            );
        }

        $resp = $response->json();
        $redirectUrl = (string) ($resp['redirectUrl'] ?? '');
        if ($redirectUrl === '') {
            throw new \RuntimeException('PhonePe returned no redirectUrl.');
        }

        return [
            'phonepe_merchant_order_id' => $merchantOrderId,
            'phonepe_order_id' => (string) ($resp['orderId'] ?? ''),
            'redirect_url' => $redirectUrl,
            'state' => (string) ($resp['state'] ?? 'PENDING'),
            'expire_at' => (int) ($resp['expireAt'] ?? 0),
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'mode' => $this->mode(),
        ];
    }

    /**
     * Verify by polling PhonePe's order status API.
     */
    public function verifyPayment(array $data, Subscription $subscription): bool
    {
        $merchantOrderId = (string) ($data['phonepe_merchant_order_id'] ?? '');
        if ($merchantOrderId === '' || ! $this->isConfigured()) {
            return false;
        }

        // Anti-substitution: bind to the merchant order id we stored on
        // this subscription during createOrder. (Phase 2a security audit, Vuln 1.)
        $persisted = (string) (($subscription->gateway_metadata['phonepe_merchant_order_id'] ?? null));
        if ($persisted === '' || ! hash_equals($persisted, $merchantOrderId)) {
            return false;
        }

        $response = $this->authenticatedRequest()
            ->get($this->payApiBase().'/checkout/v2/order/'.urlencode($merchantOrderId).'/status');

        if (! $response->successful()) {
            return false;
        }

        return (string) $response->json('state') === 'COMPLETED';
    }

    public function verifyValidationRules(): array
    {
        return [
            'phonepe_merchant_order_id' => 'required|string|max:100',
        ];
    }

    public function applyOrderIdsToSubscription(Subscription $subscription, array $orderResponse): void
    {
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'phonepe_merchant_order_id' => (string) ($orderResponse['phonepe_merchant_order_id'] ?? ''),
                'phonepe_order_id' => (string) ($orderResponse['phonepe_order_id'] ?? ''),
                'phonepe_redirect_url' => (string) ($orderResponse['redirect_url'] ?? ''),
                'phonepe_state' => (string) ($orderResponse['state'] ?? 'PENDING'),
            ]),
        ]);
    }

    public function applyVerifiedIdsToSubscription(Subscription $subscription, array $verifyData): void
    {
        // Verify path doesn't introduce new IDs — transaction_id arrives
        // via the webhook. Just update state.
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'phonepe_state' => 'COMPLETED',
            ]),
        ]);
    }

    /* ==================================================================
     |  Webhook
     | ================================================================== */

    /**
     * Handle a PhonePe Standard Checkout webhook.
     *
     * Authentication: PhonePe sends `Authorization: <sha256-hex>` where
     * the hash is over the literal string "{merchant_username}:{merchant_password}".
     * Verified with hash_equals.
     *
     * Dedupe: PhonePe doesn't include a unique event id (à la Stripe's
     * evt_xxx). We synthesize one from the transaction id + event type
     * — falls back to merchantOrderId + event type when transaction id
     * isn't on the payload.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $username = $this->webhookUsername();
        $password = $this->webhookPassword();

        if (empty($username) || empty($password)) {
            Log::warning('PhonePe webhook received but webhook_username / webhook_password not configured.');

            return response()->json(['error' => 'Webhook credentials not configured.'], 503);
        }

        $authHeader = (string) $request->header('Authorization', '');
        if ($authHeader === '') {
            return response()->json(['error' => 'Missing Authorization header.'], 401);
        }

        $expected = hash('sha256', $username.':'.$password);
        if (! hash_equals($expected, $authHeader)) {
            Log::warning('PhonePe webhook signature mismatch.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $rawBody = $request->getContent();
        $event = json_decode($rawBody, true);
        if (! is_array($event)) {
            return response()->json(['error' => 'Malformed JSON.'], 422);
        }

        $eventType = (string) ($event['event'] ?? '');
        $payload = $event['payload'] ?? [];
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');

        if ($eventType === '' || $merchantOrderId === '') {
            return response()->json(['error' => 'Missing event or merchantOrderId.'], 422);
        }

        // Synthesize a stable event_id. transactionId is preferred (one
        // per actual webhook delivery); merchantOrderId+event is the
        // fallback so we still dedupe retries when transactionId is
        // absent (some refund event shapes don't carry it).
        $transactionId = (string) ($payload['paymentDetails'][0]['transactionId'] ?? '');
        $eventId = $transactionId !== ''
            ? $transactionId.':'.$eventType
            : $merchantOrderId.':'.$eventType;

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

        $subscription = Subscription::where('gateway', $this->getSlug())
            ->where('gateway_metadata->phonepe_merchant_order_id', $merchantOrderId)
            ->first();

        if (! $subscription) {
            WebhookEvent::where('gateway', $this->getSlug())
                ->where('event_id', $eventId)
                ->update(['status' => WebhookEvent::STATUS_IGNORED]);

            return response()->json(['status' => 'ignored'], 200);
        }

        $activator = app(SubscriptionActivator::class);

        $outcome = match ($eventType) {
            'checkout.order.completed' => $this->processCompleted($subscription, $payload, $activator),
            'checkout.order.failed' => $this->processFailed($subscription, $activator),
            'pg.refund.completed' => $this->processRefundCompleted($subscription, $activator),
            // pg.refund.failed: a refund attempt didn't succeed. Don't
            // touch payment state — log + ignore. Buyer / admin reconciles
            // manually.
            default => 'ignored',
        };

        if ($outcome === 'ignored') {
            WebhookEvent::where('gateway', $this->getSlug())
                ->where('event_id', $eventId)
                ->update(['status' => WebhookEvent::STATUS_IGNORED]);
        }

        return response()->json(['status' => $outcome], 200);
    }

    private function processCompleted(Subscription $subscription, array $payload, SubscriptionActivator $activator): string
    {
        $existing = $subscription->gateway_metadata ?? [];
        $first = $payload['paymentDetails'][0] ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'phonepe_transaction_id' => (string) ($first['transactionId'] ?? ''),
                'phonepe_payment_mode' => (string) ($first['paymentMode'] ?? ''),
                'phonepe_state' => 'COMPLETED',
            ]),
        ]);

        $activator->activate($subscription);

        return 'processed';
    }

    private function processFailed(Subscription $subscription, SubscriptionActivator $activator): string
    {
        $activator->markFailed($subscription);

        return 'processed';
    }

    private function processRefundCompleted(Subscription $subscription, SubscriptionActivator $activator): string
    {
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'phonepe_state' => 'REFUND_COMPLETED',
            ]),
        ]);

        $activator->markRefunded($subscription);

        return 'processed';
    }

    /* ==================================================================
     |  Internals
     | ================================================================== */

    private function clientId(): ?string
    {
        return config('services.phonepe.client_id');
    }

    private function clientSecret(): ?string
    {
        return config('services.phonepe.client_secret');
    }

    private function clientVersion(): ?string
    {
        return config('services.phonepe.client_version');
    }

    private function webhookUsername(): ?string
    {
        return config('services.phonepe.webhook_username');
    }

    private function webhookPassword(): ?string
    {
        return config('services.phonepe.webhook_password');
    }

    private function mode(): string
    {
        return config('services.phonepe.mode') === 'production' ? 'production' : 'sandbox';
    }

    /**
     * Base URL for the Pay / Status / Refund endpoints. Different on
     * sandbox vs production — sandbox uses the pg-sandbox umbrella while
     * production routes via the bare /apis/pg path.
     */
    private function payApiBase(): string
    {
        return $this->mode() === 'production'
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    /**
     * OAuth token endpoint. PhonePe routes the production token endpoint
     * through a SEPARATE host (identity-manager) — so this differs from
     * payApiBase() and is computed independently.
     */
    private function oauthUrl(): string
    {
        return $this->mode() === 'production'
            ? 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';
    }

    /**
     * OAuth2 client_credentials. We cache the token using PhonePe's
     * `expires_at` (epoch seconds) when present — minus a 60s safety
     * margin to avoid a 401 race at the boundary. Falls back to a 30-min
     * fixed TTL if expires_at is missing or in the past.
     */
    private function getAccessToken(): string
    {
        $cacheKey = $this->tokenCacheKey();
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::withoutVerifying()
            ->asForm()
            ->acceptJson()
            ->post($this->oauthUrl(), [
                'client_id' => $this->clientId(),
                'client_version' => $this->clientVersion(),
                'client_secret' => $this->clientSecret(),
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'PhonePe OAuth token request failed: '.$response->status().' '.$response->body(),
            );
        }

        $token = (string) $response->json('access_token', '');
        if ($token === '') {
            throw new \RuntimeException('PhonePe returned no access_token.');
        }

        $expiresAt = (int) $response->json('expires_at', 0);
        $ttl = $expiresAt > time() + self::TOKEN_SAFETY_MARGIN_SECONDS
            ? ($expiresAt - time() - self::TOKEN_SAFETY_MARGIN_SECONDS)
            : self::TOKEN_FALLBACK_TTL_SECONDS;

        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    private function tokenCacheKey(): string
    {
        return 'phonepe_access_token_'.$this->mode();
    }

    /**
     * Pre-configured Http pending-request: O-Bearer auth + JSON content type.
     * Note: `O-Bearer` (NOT `Bearer`) per PhonePe spec.
     */
    private function authenticatedRequest()
    {
        return Http::withoutVerifying()
            ->withHeaders([
                'Authorization' => 'O-Bearer '.$this->getAccessToken(),
            ])
            ->acceptJson();
    }

    /**
     * Build the metaInfo PhonePe echoes back. Only udf1-udf5 are
     * supported. We pack the most useful fields for support /
     * reconciliation.
     */
    private function buildMetaInfo(array $metadata): array
    {
        return [
            'udf1' => (string) ($metadata['user_id'] ?? ''),
            'udf2' => (string) ($metadata['plan_id'] ?? ''),
            'udf3' => (string) ($metadata['plan_name'] ?? ''),
            'udf4' => (string) ($metadata['coupon_code'] ?? ''),
            'udf5' => (string) ($metadata['subscription_id'] ?? ''),
        ];
    }
}
