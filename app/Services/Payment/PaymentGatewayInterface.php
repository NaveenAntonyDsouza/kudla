<?php

namespace App\Services\Payment;

use App\Models\Subscription;

/**
 * Contract every payment gateway must implement.
 *
 * Designed for the CodeCanyon multi-gateway use case:
 *   - Each implementation is self-contained (own SDK config, own
 *     signature scheme, own error mapping).
 *   - Manager-driven (App\Services\Payment\PaymentGatewayManager
 *     resolves gateways by slug at runtime).
 *   - Buyer/admin enables / disables / configures gateways via
 *     SiteSetting + Filament; isConfigured() reports on this.
 *
 * Why minimal (5 methods):
 *   * Each gateway has subtle quirks (signature schemes, idempotency
 *     models, callback formats). Over-abstracting tends to leak.
 *     We commit to only the surface the controller layer needs and
 *     let each gateway own its internals.
 *   * Refunds, subscriptions, customer-vault APIs are deferred —
 *     add to the interface when there's a concrete second
 *     implementation proving each abstraction.
 *
 * URL pattern: /payment/{gateway-slug}/order, /payment/{gateway-slug}/verify.
 * The slug returned by getSlug() must match the URL fragment.
 *
 * Reference research:
 *   - Razorpay Subscription Docs: https://razorpay.com/payment-gateway/
 *   - Stripe Payment Intents: https://stripe.com/docs/payments/payment-intents
 *   - PayPal Orders v2: https://developer.paypal.com/docs/api/orders/v2/
 */
interface PaymentGatewayInterface
{
    /**
     * Stable URL-safe slug used in routes + DB.
     * Examples: "razorpay", "stripe", "paypal", "paytm", "phonepe".
     * Must be lowercase, [a-z0-9-]+.
     */
    public function getSlug(): string;

    /**
     * Human-readable display name shown in admin + Flutter checkout UI.
     * Examples: "Razorpay", "Stripe", "PayPal", "Paytm", "PhonePe".
     */
    public function getName(): string;

    /**
     * Is this gateway both ENABLED (admin toggle on) and CONFIGURED
     * (API credentials present)? When false, the controller returns
     * 422 GATEWAY_NOT_CONFIGURED to the caller and a request never
     * reaches createOrder() / verifyPayment().
     *
     * Defensive — must never throw. Return false on any inability
     * to resolve config.
     */
    public function isConfigured(): bool;

    /**
     * Create an order/intent on the gateway side and return the
     * gateway-specific payload Flutter needs to invoke the gateway's
     * client SDK.
     *
     * @param  int  $amountInPaise  Amount to charge, in paise (₹1 = 100 paise)
     * @param  array  $metadata  Receipt id, plan id, user id, coupon code, etc. Pass-through to gateway.
     * @return array  Gateway-specific payload — Flutter dispatches based on
     *                slug. Razorpay: {order_id, key_id, amount, currency, …}.
     *                Stripe: {client_secret, payment_intent_id}.
     *                PayPal: {order_id, approval_url}.
     * @throws \RuntimeException on gateway API failure / network error
     */
    public function createOrder(int $amountInPaise, array $metadata = []): array;

    /**
     * Verify a payment-completion callback.
     *
     * @param  array  $data  Gateway-specific verify payload received from
     *                       the client (e.g. razorpay_payment_id +
     *                       razorpay_order_id + razorpay_signature for
     *                       Razorpay; payment_intent_id + status for
     *                       Stripe).
     * @return bool  true when payment is authentic and successful.
     */
    public function verifyPayment(array $data): bool;

    /**
     * Validation rules for the verify endpoint's request body.
     * Lets each gateway declare its own required fields without the
     * controller having to switch on slug. Merged with the controller's
     * common rules (subscription_id required, etc.).
     *
     * @return array<string, string|array>  Laravel validation rules.
     */
    public function verifyValidationRules(): array;

    /**
     * Persist gateway-specific IDs to the Subscription row after
     * order creation. Each gateway owns the choice between dedicated
     * columns (e.g. razorpay_order_id) and JSON metadata.
     *
     * @param  Subscription  $subscription  The pending subscription row.
     * @param  array  $orderResponse  Whatever createOrder() returned.
     */
    public function applyOrderIdsToSubscription(Subscription $subscription, array $orderResponse): void;

    /**
     * Persist gateway-specific IDs to the Subscription row after
     * successful verification.
     *
     * @param  Subscription  $subscription  The subscription being marked paid.
     * @param  array  $verifyData  Whatever verifyPayment() received.
     */
    public function applyVerifiedIdsToSubscription(Subscription $subscription, array $verifyData): void;
}
