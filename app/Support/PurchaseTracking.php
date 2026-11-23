<?php

namespace App\Support;

use App\Models\MembershipPlan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;

/**
 * The 'purchase' key moment for GA4 / GTM / Meta Pixel / PostHog, sent once per payment.
 *
 * Two ways a payment ends:
 *  - the member's browser comes back to "Payment successful" (Razorpay verify, PhonePe
 *    return): queue() puts the event in the session and the next page sends it;
 *  - the gateway's server notice (webhook) or staff activates it with no browser in the
 *    request: due() remembers it for that member, and sendDue() queues it on the member's
 *    next tracked page, on any device, within KEEP_DAYS.
 *
 * Weekly review 23 Nov 2026: KM100793's Razorpay payment on 22 Nov (in the Android app)
 * was activated by the webhook and the page never posted back, so Analytics, PostHog and
 * Meta never heard of it (GA4 ₹3,000 against ₹4,000 paid).
 *
 * A "sent" marker per payment makes sure both ways together still count it once. Nothing
 * personal is sent: payment number, rupees, plan and coupon code only.
 */
final class PurchaseTracking
{
    public const DUE = 'analytics.purchase-due.';   // + user id → [subscription ids]

    public const SENT = 'analytics.purchase-sent.'; // + subscription id

    public const KEEP_DAYS = 14;

    /** The member's browser saw the payment succeed: send it from the next page. */
    public static function queue(?Subscription $subscription): void
    {
        if (! $subscription || ! $subscription->id) {
            return;
        }
        if (! Cache::add(self::SENT.$subscription->id, 1, now()->addDays(90))) {
            return; // already sent (refresh, second success path, or a due purchase)
        }

        Analytics::track('purchase', self::params($subscription), 'Purchase');
    }

    /** Activated with no browser in the request (webhook, staff): remember it for the member. */
    public static function due(Subscription $subscription): void
    {
        rescue(function () use ($subscription) {
            if (! $subscription->id || ! $subscription->user_id) {
                return;
            }
            $key = self::DUE.$subscription->user_id;
            $ids = array_values(array_unique(array_merge((array) Cache::get($key, []), [(int) $subscription->id])));
            Cache::put($key, $ids, now()->addDays(self::KEEP_DAYS));
        }, null, false);
    }

    /** On every tracked page: queue the signed-in member's paid purchases not sent yet. */
    public static function sendDue(): void
    {
        try {
            $user = auth()->user();
            if (! $user || ! request()->hasSession()) {
                return;
            }
            $ids = (array) Cache::pull(self::DUE.$user->id, []);
            if (! $ids) {
                return;
            }
            Subscription::whereIn('id', $ids)->where('user_id', $user->id)->where('payment_status', 'paid')
                ->orderBy('id')->get()->each(fn (Subscription $s) => self::queue($s));
        } catch (\Throwable $e) {
            report($e); // tracking must never break a page
        }
    }

    /** GA4 ecommerce parameters (amounts are stored in paise). */
    public static function params(Subscription $subscription): array
    {
        $plan = MembershipPlan::find($subscription->plan_id);
        $value = round(((int) $subscription->amount) / 100, 2);

        return [
            'transaction_id' => (string) $subscription->id,
            'value' => $value,
            'currency' => 'INR',
            'coupon' => (string) ($subscription->coupon_code ?? ''),
            'items' => [[
                'item_id' => 'plan-'.($plan?->id ?? $subscription->plan_id),
                'item_name' => $plan?->plan_name ?? ($subscription->plan_name ?? 'Membership'),
                'price' => $value,
                'quantity' => 1,
            ]],
        ];
    }
}
