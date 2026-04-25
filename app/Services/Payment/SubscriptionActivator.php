<?php

namespace App\Services\Payment;

use App\Models\Coupon;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\UserMembership;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for "mark a subscription paid + activate the
 * matching UserMembership."
 *
 * Called from two paths that converge on the same outcome:
 *   1. Synchronous: PaymentController::verifyPayment (after the user
 *      completes the in-app payment flow and Flutter posts the
 *      gateway callback).
 *   2. Asynchronous: PaymentGatewayInterface::handleWebhook (after the
 *      gateway server pushes the payment.captured event).
 *
 * Both paths can fire — typically the synchronous /verify wins by a
 * few seconds and the webhook arrives second. The activator is
 * **idempotent** so the second call is a no-op:
 *
 *   activate() → if subscription is already paid, return immediately.
 *                Otherwise mark paid, set timestamps, create
 *                UserMembership, deactivate priors, record coupon usage.
 *
 * Persistence of gateway-specific IDs (razorpay_payment_id,
 * stripe_payment_intent_id, etc.) is NOT this service's job — each
 * gateway service handles that via applyVerifiedIdsToSubscription
 * before calling activate().
 */
class SubscriptionActivator
{
    /**
     * Activate a subscription. Idempotent.
     *
     * Returns true when activation actually fired (subscription was
     * pending and is now paid); false when the subscription was already
     * paid (no-op) or activation couldn't proceed (plan deleted, etc.).
     */
    public function activate(Subscription $subscription): bool
    {
        // Idempotent — if already paid, both verify and webhook treat
        // this as success. Caller can branch on the return value if
        // it cares.
        if ($subscription->payment_status === 'paid') {
            return false;
        }

        $plan = MembershipPlan::find($subscription->plan_id);
        if (! $plan) {
            // Edge case: plan was deleted between order and activation.
            // Mark as paid (the user did pay) but leave inactive — admin
            // reconciliation needed. Return false to signal partial.
            $subscription->update([
                'payment_status' => 'paid',
                'is_active' => false,
            ]);

            return false;
        }

        $startsAt = Carbon::today();
        $expiresAt = $startsAt->copy()->addMonths(max(1, (int) $plan->duration_months));

        $subscription->update([
            'payment_status' => 'paid',
            'is_active' => true,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
        ]);

        // Record coupon usage if applicable. Best-effort — a hiccup
        // here shouldn't fail the whole activation.
        if ($subscription->coupon_id) {
            $coupon = Coupon::find($subscription->coupon_id);
            if ($coupon) {
                try {
                    $coupon->recordUsage(
                        userId: $subscription->user_id,
                        subscriptionId: $subscription->id,
                        discountAmount: (int) $subscription->discount_amount,
                    );
                } catch (\Throwable $e) {
                    // Swallow — the subscription is paid; coupon
                    // tracking is auxiliary.
                }
            }
        }

        // Deactivate prior memberships, create the new one. Same
        // best-effort stance as coupon recording.
        try {
            UserMembership::where('user_id', $subscription->user_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            UserMembership::create([
                'user_id' => $subscription->user_id,
                'plan_id' => $plan->id,
                'is_active' => true,
                'starts_at' => $startsAt,
                'ends_at' => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            // Swallow — see above. Admin can manually create the
            // UserMembership row if this branch fires.
        }

        return true;
    }

    /**
     * Mark a subscription as failed (e.g. payment.failed webhook).
     * Idempotent — safe to call multiple times.
     */
    public function markFailed(Subscription $subscription): void
    {
        if (in_array($subscription->payment_status, ['paid', 'refunded'], true)) {
            // Already terminal in a different direction — don't flip back.
            return;
        }

        $subscription->update([
            'payment_status' => 'failed',
            'is_active' => false,
        ]);
    }

    /**
     * Mark a subscription as refunded + deactivate the matching
     * UserMembership (e.g. refund.processed webhook).
     */
    public function markRefunded(Subscription $subscription): void
    {
        $subscription->update([
            'payment_status' => 'refunded',
            'is_active' => false,
        ]);

        try {
            UserMembership::where('user_id', $subscription->user_id)
                ->where('plan_id', $subscription->plan_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        } catch (\Throwable $e) {
            // Best-effort.
        }
    }
}
