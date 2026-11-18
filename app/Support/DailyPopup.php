<?php

namespace App\Support;

use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * At most ONE pop-up per member per day, on their first page of the day
 * (Nov 2026; owner: "you decide"). In order of importance:
 *
 *  1. photo    "Add your photo"           no photo yet (PhotoPrompt)
 *  2. waiting  "Families are waiting"     interests unanswered for WAITING_DAYS+ days
 *  3. plans    "See the plans"            free members, not in their first PLANS_AFTER_DAYS
 *                                         days, at most once every PLANS_EVERY_DAYS days
 *  4. channel  "Follow our WhatsApp Channel"   once per member, ever
 *
 * Each has its own switch (Site Settings -> Member site). Never shown while
 * staff use Login as member, to staff, during sign-up, or on the pages the
 * pop-up points to. Kept honest on purpose: no countdowns or "limited
 * period" claims that are not true (India's Dark Patterns Guidelines 2023
 * list false urgency and nagging).
 */
final class DailyPopup
{
    public const PHOTO = 'photo';
    public const WAITING = 'waiting';
    public const PLANS = 'plans';
    public const CHANNEL = 'channel';

    public const WAITING_DAYS = 3;
    public const PLANS_AFTER_DAYS = 3;
    public const PLANS_EVERY_DAYS = 4;
    public const CHANNEL_AFTER_DAYS = 2;

    /**
     * Which pop-up to show on this page, or null. Choosing one uses up the
     * member's showing for the day, so only ask where it will be shown.
     */
    public static function choose(?User $user = null): ?string
    {
        try {
            $user ??= auth()->user();
            $profile = $user?->profile;
            if (! $profile || $user->staff_role_id !== null || in_array($user->role, ['admin', 'super_admin'], true) || LoginAs::active()) {
                return null;
            }
            // during sign-up, or already on the page a pop-up would send them to
            if (request()->routeIs('register*', 'onboarding.*', 'photos.*', 'membership.*', 'payment.*')) {
                return null;
            }
            if (Cache::has(self::dayKey($user))) {
                return null; // today's pop-up was already shown
            }

            $kind = self::firstDue($user);
            if ($kind === null || ! Cache::add(self::dayKey($user), $kind, now()->endOfDay())) {
                return null;
            }
            if ($kind === self::PLANS) {
                Cache::put(self::plansKey($user), true, now()->addDays(self::PLANS_EVERY_DAYS)->startOfDay());
            }
            if ($kind === self::CHANNEL) {
                Cache::forever(self::channelKey($user), true);
            }

            return $kind;
        } catch (\Throwable) {
            return null; // a nudge must never break the page
        }
    }

    private static function firstDue(User $user): ?string
    {
        $profile = $user->profile;

        if (AdminModules::photoPopup() && ! PhotoPrompt::hasPhoto($profile)) {
            return self::PHOTO;
        }

        // the rest only once registration is finished
        if (! $profile->onboarding_completed) {
            return null;
        }

        if (AdminModules::waitingPopup() && ! request()->routeIs('interests.*') && self::waitingCount($user) > 0) {
            return self::WAITING;
        }

        if (AdminModules::plansPopup() && self::plansDue($user)) {
            return self::PLANS;
        }

        if (AdminModules::channelPopup() && WhatsApp::channelUrl()
            && $user->created_at && $user->created_at->lte(now()->subDays(self::CHANNEL_AFTER_DAYS))
            && ! Cache::has(self::channelKey($user))) {
            return self::CHANNEL;
        }

        return null;
    }

    /** Members waiting for this member's reply, counted only when one has waited WAITING_DAYS+ days. */
    public static function waitingCount(User $user): int
    {
        $pending = $user->profile->receivedInterests()->where('status', 'pending');
        if (! (clone $pending)->where('created_at', '<=', now()->subDays(self::WAITING_DAYS))->exists()) {
            return 0;
        }

        return $pending->count();
    }

    private static function plansDue(User $user): bool
    {
        if ($user->isPremium() || Cache::has(self::plansKey($user))) {
            return false;
        }
        if (! $user->created_at || $user->created_at->gt(now()->subDays(self::PLANS_AFTER_DAYS))) {
            return false;
        }

        return self::paidPlans()->isNotEmpty();
    }

    /** Paid plans for the pop-up, cheapest first, at most three. */
    public static function paidPlans()
    {
        return MembershipPlan::where('is_active', true)->where('price_inr', '>', 0)
            ->orderBy('price_inr')->limit(3)->get();
    }

    private static function dayKey(User $user): string
    {
        // the photo pop-up's key from before (16 Nov 2026), so a deploy doesn't show a second pop-up today
        return 'photo-popup:'.$user->id.':'.now()->toDateString();
    }

    private static function plansKey(User $user): string
    {
        return 'plans-popup-shown:'.$user->id;
    }

    private static function channelKey(User $user): string
    {
        return 'channel-popup-shown:'.$user->id;
    }
}
