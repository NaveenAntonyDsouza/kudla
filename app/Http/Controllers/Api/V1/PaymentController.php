<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\SubscriptionActivator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payment-gateway API surface — multi-gateway by design.
 *
 *   POST /api/v1/payment/{gateway}/order     create gateway order, persist Subscription(payment_status=pending)
 *   POST /api/v1/payment/{gateway}/verify    verify gateway callback, mark paid, activate UserMembership
 *
 * The {gateway} URL fragment maps to the gateway's slug (e.g. "razorpay",
 * "stripe", "paypal"). PaymentGatewayManager resolves the slug to a
 * concrete service implementing PaymentGatewayInterface. Adding a new
 * gateway is a single line in AppServiceProvider — no controller /
 * route / test changes required.
 *
 * The controller owns the orchestration:
 *   - Resolve gateway via manager (returns 404 / 422 on unknown / disabled)
 *   - Resolve plan + apply coupon (delegates math to Coupon::validateFor)
 *   - Persist a pending Subscription row
 *   - Delegate gateway-specific work (createOrder, verifyPayment,
 *     applyOrderIdsToSubscription, applyVerifiedIdsToSubscription)
 *   - On verified: flip subscription to paid, create UserMembership,
 *     record coupon usage, deactivate prior memberships
 *
 * Each gateway service owns its own SDK config, signature scheme,
 * webhook handler — interface stays minimal (5 + 3 helper methods).
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-04-razorpay-order-verify.md
 */
class PaymentController extends BaseApiController
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    /* ==================================================================
     |  POST /payment/{gateway}/order
     | ================================================================== */

    /**
     * Create a gateway order, persist a pending Subscription, return
     * the gateway-specific payload Flutter needs to invoke the gateway
     * client SDK.
     *
     * @authenticated
     *
     * @group Payment
     *
     * @urlParam gateway string required Gateway slug (razorpay, stripe, paypal, paytm, phonepe).
     *
     * @bodyParam plan_id integer required Plan to subscribe to.
     * @bodyParam coupon_code string Optional coupon code.
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "subscription_id": 123,
     *     "gateway": "razorpay",
     *     "amount_inr": 2400,
     *     "currency": "INR",
     *     "gateway_data": {
     *       "order_id": "order_M1zXabc...",
     *       "key_id": "rzp_test_xxxxx",
     *       "amount": 240000,
     *       "currency": "INR",
     *       "status": "created"
     *     }
     *   }
     * }
     *
     * @response 404 scenario="unknown-gateway" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     * @response 404 scenario="unknown-plan" {"success": false, "error": {"code": "NOT_FOUND", "message": "Plan not found."}}
     * @response 422 scenario="gateway-not-configured" {"success": false, "error": {"code": "GATEWAY_NOT_CONFIGURED", "message": "..."}}
     * @response 422 scenario="coupon-invalid" {"success": false, "error": {"code": "COUPON_INVALID", "message": "..."}}
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "..."}}
     * @response 502 scenario="gateway-error" {"success": false, "error": {"code": "GATEWAY_ERROR", "message": "..."}}
     *
     * @response 201 scenario="full-discount-coupon" {
     *   "success": true,
     *   "data": {
     *     "subscription_id": 124,
     *     "gateway": "coupon",
     *     "amount_inr": 0,
     *     "currency": "INR",
     *     "gateway_data": null,
     *     "is_active": true,
     *     "payment_status": "paid",
     *     "activated_via": "full_discount_coupon",
     *     "starts_at": "2026-04-27T00:00:00+05:30",
     *     "expires_at": "2027-04-27T00:00:00+05:30"
     *   }
     * }
     */
    public function createOrder(Request $request, string $gatewaySlug): JsonResponse
    {
        $gateway = $this->gateways->forSlug($gatewaySlug);
        if (! $gateway) {
            return ApiResponse::error('NOT_FOUND', 'Unknown payment gateway.', null, 404);
        }
        if (! $gateway->isConfigured()) {
            return ApiResponse::error(
                'GATEWAY_NOT_CONFIGURED',
                "{$gateway->getName()} is not currently available. Please choose another payment method.",
                null,
                422,
            );
        }

        $data = $request->validate([
            'plan_id' => 'required|integer',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $plan = MembershipPlan::find($data['plan_id']);
        if (! $plan || ! $plan->is_active) {
            return ApiResponse::error('NOT_FOUND', 'Plan not found.', null, 404);
        }

        $user = $request->user();

        // Coupon application — same logic as the /coupon/validate endpoint
        // (delegates to Coupon::validateFor). Math is in paise; subscription
        // amount fields stored in paise to match the Razorpay convention
        // already used by web.
        $originalPaise = ((int) $plan->price_inr) * 100;
        $discountPaise = 0;
        $coupon = null;

        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::query()
                ->whereRaw('LOWER(code) = ?', [strtolower($data['coupon_code'])])
                ->first();
            if (! $coupon) {
                return ApiResponse::error('COUPON_INVALID', 'Coupon not found.', null, 422);
            }

            $check = $coupon->validateFor($plan->id, $originalPaise, $user->id);
            if (! $check['valid']) {
                return ApiResponse::error(
                    'COUPON_INVALID',
                    (string) ($check['message'] ?? 'Coupon is not valid.'),
                    null,
                    422,
                );
            }
            $discountPaise = (int) $check['discount'];
        }

        $finalPaise = max(0, $originalPaise - $discountPaise);

        // Persist the Subscription as pending BEFORE calling the gateway
        // — this gives us a stable id we can pass back as the
        // subscription_id and use to track the order even if the gateway
        // call fails or the user abandons the flow.
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id ?? null,
            'plan_id' => $plan->id,
            'plan_name' => (string) $plan->plan_name,
            'gateway' => $gateway->getSlug(),
            'gateway_metadata' => null,
            'coupon_id' => $coupon?->id,
            'coupon_code' => $coupon?->code,
            'discount_amount' => $discountPaise,
            'original_amount' => $originalPaise,
            'amount' => $finalPaise,
            'payment_status' => 'pending',
            'is_active' => false,
        ]);

        // Full-discount coupon shortcut — when the discount equals the
        // plan price, the user owes nothing. Skip the gateway entirely
        // and activate the membership through the same idempotent
        // SubscriptionActivator that /verify uses. Flutter sees the
        // `activated_via` field on the response and routes straight to
        // the "membership active" UI without making a /verify call.
        // Acceptance reference: week-04-acceptance.md edge case #5.
        if ($finalPaise === 0 && $coupon !== null) {
            $subscription->update([
                'gateway' => 'coupon',
                'gateway_metadata' => [
                    'activated_via' => 'full_discount_coupon',
                    'original_gateway' => $gateway->getSlug(),
                    'coupon_code' => (string) $coupon->code,
                ],
            ]);

            app(SubscriptionActivator::class)->activate($subscription);
            $subscription->refresh();

            return ApiResponse::created([
                'subscription_id' => (int) $subscription->id,
                'gateway' => 'coupon',
                'amount_inr' => 0,
                'currency' => 'INR',
                'gateway_data' => null,
                'is_active' => (bool) $subscription->is_active,
                'payment_status' => (string) $subscription->payment_status,
                'activated_via' => 'full_discount_coupon',
                'starts_at' => $subscription->starts_at?->toIso8601String(),
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ]);
        }

        // Call the gateway. On any failure, mark the subscription as
        // failed (don't delete — keeps reconciliation history) and
        // return 502 to Flutter.
        try {
            $gatewayResponse = $gateway->createOrder($finalPaise, [
                'receipt' => 'sub_'.$subscription->id,
                'subscription_id' => (string) $subscription->id,
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
                'plan_name' => (string) $plan->plan_name,
                'coupon_code' => $coupon?->code ?? '',
            ]);
        } catch (\Throwable $e) {
            $subscription->update(['payment_status' => 'failed']);

            return ApiResponse::error(
                'GATEWAY_ERROR',
                'Could not create order with the payment gateway. Please try again.',
                null,
                502,
            );
        }

        // Persist gateway-specific IDs (Razorpay → razorpay_order_id;
        // others → gateway_metadata JSON).
        $gateway->applyOrderIdsToSubscription($subscription, $gatewayResponse);
        $subscription->refresh();

        return ApiResponse::created([
            'subscription_id' => (int) $subscription->id,
            'gateway' => $gateway->getSlug(),
            'amount_inr' => (int) round($finalPaise / 100),
            'currency' => 'INR',
            'gateway_data' => $gatewayResponse,
        ]);
    }

    /* ==================================================================
     |  POST /payment/{gateway}/verify
     | ================================================================== */

    /**
     * Verify a gateway callback after Flutter completes the in-app
     * payment flow. On valid signature, marks the subscription paid,
     * creates / extends UserMembership, records coupon usage, and
     * deactivates prior memberships.
     *
     * @authenticated
     *
     * @group Payment
     *
     * @urlParam gateway string required Gateway slug.
     *
     * @bodyParam subscription_id integer required Pending subscription id from /order.
     * @bodyParam razorpay_order_id string Required for Razorpay.
     * @bodyParam razorpay_payment_id string Required for Razorpay.
     * @bodyParam razorpay_signature string Required for Razorpay.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "subscription_id": 123,
     *     "payment_status": "paid",
     *     "is_active": true,
     *     "starts_at": "2026-04-25T...",
     *     "expires_at": "2026-10-25T...",
     *     "membership": {"plan_id": 5, "plan_name": "Diamond Plus", "is_premium": true}
     *   }
     * }
     *
     * @response 200 scenario="already-verified" {"success": true, "data": {"already_verified": true, "subscription_id": 123}}
     * @response 403 scenario="not-owner" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="unknown-gateway" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     * @response 404 scenario="subscription-not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "Subscription not found."}}
     * @response 422 scenario="signature-invalid" {"success": false, "error": {"code": "SIGNATURE_INVALID", "message": "..."}}
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "..."}}
     */
    public function verifyPayment(Request $request, string $gatewaySlug): JsonResponse
    {
        $gateway = $this->gateways->forSlug($gatewaySlug);
        if (! $gateway) {
            return ApiResponse::error('NOT_FOUND', 'Unknown payment gateway.', null, 404);
        }
        if (! $gateway->isConfigured()) {
            return ApiResponse::error(
                'GATEWAY_NOT_CONFIGURED',
                "{$gateway->getName()} is not currently available.",
                null,
                422,
            );
        }

        // Common rules + gateway-specific rules.
        $rules = array_merge(
            ['subscription_id' => 'required|integer'],
            $gateway->verifyValidationRules(),
        );
        $data = $request->validate($rules);

        $subscription = Subscription::find($data['subscription_id']);
        if (! $subscription) {
            return ApiResponse::error('NOT_FOUND', 'Subscription not found.', null, 404);
        }
        if ($subscription->user_id !== $request->user()->id) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'You do not have permission to verify this payment.',
                null,
                403,
            );
        }

        // Idempotency — Flutter may retry verify on flaky networks.
        // Returning a 200 with already_verified=true lets the client
        // proceed without showing a confusing error.
        if ($subscription->payment_status === 'paid') {
            return ApiResponse::ok([
                'already_verified' => true,
                'subscription_id' => (int) $subscription->id,
            ]);
        }

        // Pass the resolved Subscription so the gateway can bind the
        // verify call to *this* subscription's persisted IDs — defends
        // against a user paying one of their own pending subs and
        // replaying the gateway IDs against another. See
        // PaymentGatewayInterface::verifyPayment() docblock + Phase 2a
        // security audit Vuln 1.
        if (! $gateway->verifyPayment($data, $subscription)) {
            return ApiResponse::error(
                'SIGNATURE_INVALID',
                'Payment signature could not be verified. Please contact support.',
                null,
                422,
            );
        }

        // Persist gateway-specific verified IDs (razorpay_payment_id +
        // razorpay_signature for Razorpay; gateway_metadata JSON for
        // future gateways). Done BEFORE activation so the subscription
        // row carries the verifying gateway IDs even if activation
        // partially fails (plan deleted, user_memberships outage, etc.).
        $gateway->applyVerifiedIdsToSubscription($subscription, $data);

        // Activation: mark paid, set timestamps, create UserMembership,
        // deactivate priors, record coupon usage. Idempotent — same
        // service is called by the webhook handler too.
        $activator = app(SubscriptionActivator::class);
        $activated = $activator->activate($subscription);

        // If activate() returned false AND status is still paid, the
        // plan was deleted between order and verify — surface a 422 so
        // Flutter knows to contact support.
        $subscription->refresh();
        if (! $activated && $subscription->payment_status === 'paid' && ! $subscription->is_active) {
            return ApiResponse::error(
                'PLAN_GONE',
                'The plan associated with this payment is no longer available. Contact support.',
                null,
                422,
            );
        }

        $plan = MembershipPlan::find($subscription->plan_id);

        return ApiResponse::ok([
            'subscription_id' => (int) $subscription->id,
            'payment_status' => (string) $subscription->payment_status,
            'is_active' => (bool) $subscription->is_active,
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'expires_at' => $subscription->expires_at?->toIso8601String(),
            'membership' => $plan ? [
                'plan_id' => (int) $plan->id,
                'plan_name' => (string) $plan->plan_name,
                'is_premium' => (bool) $subscription->is_active,
            ] : null,
        ]);
    }

    /* ==================================================================
     |  POST /webhooks/{gateway}
     | ================================================================== */

    /**
     * Inbound webhook endpoint for any registered payment gateway.
     * Each gateway owns its own signature scheme + event dispatch
     * via PaymentGatewayInterface::handleWebhook. The controller
     * just resolves the slug and delegates.
     *
     * NO authentication on this route — gateway servers can't carry
     * Sanctum tokens. Authenticity is established by the per-gateway
     * signature check inside handleWebhook.
     *
     * @unauthenticated
     *
     * @group Payment
     *
     * @urlParam gateway string required Gateway slug.
     *
     * @response 200 scenario="processed" {"status": "processed"}
     * @response 200 scenario="duplicate" {"status": "duplicate"}
     * @response 200 scenario="ignored-event-type" {"status": "ignored"}
     * @response 401 scenario="invalid-signature" {"error": "Invalid signature."}
     * @response 404 scenario="unknown-gateway" {"error": "Unknown payment gateway."}
     * @response 422 scenario="malformed-payload" {"error": "Malformed JSON."}
     * @response 503 scenario="webhook-not-configured" {"error": "Webhook secret not configured."}
     */
    public function webhook(Request $request, string $gatewaySlug): JsonResponse
    {
        $gateway = $this->gateways->forSlug($gatewaySlug);
        if (! $gateway) {
            // Don't even acknowledge an unknown slug. Returning 404
            // (not 200) so a misconfigured webhook URL gets surfaced
            // in the gateway dashboard.
            return response()->json(['error' => 'Unknown payment gateway.'], 404);
        }

        // We deliberately allow webhooks against gateways that aren't
        // fully configured for OUTBOUND order creation — the inbound
        // signature check inside handleWebhook is what guards the
        // gateway-specific verification step.
        return $gateway->handleWebhook($request);
    }
}
