<?php

namespace App\Services\Payment;

use App\Models\Subscription;
use Illuminate\Support\Facades\Http;

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
     */
    public function verifyPayment(array $data): bool
    {
        $orderId = (string) ($data['razorpay_order_id'] ?? '');
        $paymentId = (string) ($data['razorpay_payment_id'] ?? '');
        $signature = (string) ($data['razorpay_signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '' || ! $this->isConfigured()) {
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
