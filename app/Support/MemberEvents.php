<?php

namespace App\Support;

use App\Models\MemberEvent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Records the steps towards paying and the moments a free member is stopped by a
 * paid feature (owner, 18 Nov 2026: "are you collecting enough metrics regarding
 * payment?" — the site kept each payment attempt, but not the plans page, coupon
 * tries, a closed payment window, the gateway's failure reason, or what made a
 * member want to pay).
 *
 * Never throws and never slows a page: a failure to record is ignored. Never stores
 * names, emails or phone numbers. Staff using Login as member are not recorded.
 *
 * Switch: Settings → Site Settings → Staff tools → "Payment funnel" (on by default).
 */
class MemberEvents
{
    public const PLANS_VIEWED = 'plans_viewed';
    public const PLAN_CHOSEN = 'plan_chosen';
    public const COUPON_TRIED = 'coupon_tried';
    public const CHECKOUT_STARTED = 'checkout_started';
    public const CHECKOUT_CLOSED = 'checkout_closed';
    public const PAYMENT_FAILED = 'payment_failed';
    public const PAYWALL = 'paywall';

    /** A new account created on sign-up step 1; subject = where it started: home / register_page / other (22 Nov 2026). */
    public const SIGNUP_STARTED = 'signup_started';

    /** Paid features a free member can be stopped by (the paywall event's subject). */
    public const FEATURES = [
        'contact' => 'See phone / WhatsApp',
        'chat_message' => 'Send a chat message',
        'personal_message' => 'Write own message with an interest',
        'interest_limit' => 'Daily interest limit reached',
    ];

    /**
     * Record one event. $onceKey makes it count at most once a day for that key
     * (e.g. the same member opening the plans page five times).
     */
    public static function record(string $event, ?string $subject = null, array $data = [], ?User $user = null, ?string $onceKey = null): void
    {
        try {
            if (! AdminModules::paymentInsights() || LoginAs::active()) {
                return;
            }
            $user ??= auth()->user();
            // staff accounts are not members
            if ($user && ($user->staff_role_id || in_array($user->role, ['admin', 'super_admin'], true))) {
                return;
            }

            if ($onceKey !== null) {
                $key = 'member-event:'.$event.':'.($user?->id ?? 'guest-'.session()->getId()).':'.$onceKey.':'.now()->toDateString();
                if (! Cache::add($key, true, now()->endOfDay())) {
                    return;
                }
            }

            MemberEvent::create([
                'user_id' => $user?->id,
                'event' => $event,
                'subject' => $subject !== null ? Str::limit($subject, 60, '') : null,
                'data' => $data === [] ? null : $data,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // measuring must never get in a member's way
        }
    }

    /** A free member stopped by a paid feature; counted once a day per member, feature and (for contacts) profile. */
    public static function paywall(string $feature, ?User $user = null, ?int $targetProfileId = null): void
    {
        self::record(self::PAYWALL, $feature, $targetProfileId ? ['target' => $targetProfileId] : [], $user, $feature.':'.($targetProfileId ?? '-'));
    }
}
