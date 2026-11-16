<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * "Add your photo" nudges for members with no photo (weekly review, 16 Nov 2026:
 * the bar on the dashboard and My Matches brought 42 photos in a week while the
 * reminder emails had almost nobody left to write to; 502 live profiles still
 * had none).
 *
 *  - the bar (x-add-photo-bar): dashboard and My Matches as before; with
 *    "more places" on, also the member's own profile page and above search
 *    results, and a close button that hides it for 3 days (owner's choice)
 *  - the pop-up (x-photo-popup): once a day, on the member's first page of the
 *    day (owner's choice: members stay signed in for weeks, so "at sign-in"
 *    would hardly ever show)
 *
 * Both flags live in the cache, per member, so they follow the member across
 * phone and computer and need no new column.
 */
class PhotoPrompt
{
    public const BAR_CLOSED_DAYS = 3;

    /** A photo waiting for approval counts: the member has done their part. */
    public static function hasPhoto(Profile $profile): bool
    {
        return $profile->profilePhotos()->whereIn('approval_status', ['approved', 'pending'])->exists();
    }

    public static function barClosed(User $user): bool
    {
        return Cache::has(self::barKey($user));
    }

    public static function closeBar(User $user): void
    {
        Cache::put(self::barKey($user), true, now()->addDays(self::BAR_CLOSED_DAYS));
    }

    /**
     * Show the pop-up on this page? True at most once per member per day.
     * Asking uses up the day's showing, so only ask where it will be shown.
     */
    public static function popupDue(?User $user = null): bool
    {
        try {
            $user ??= auth()->user();
            $profile = $user?->profile;

            if (! $profile || ! AdminModules::photoPopup()) {
                return false;
            }
            // staff using Login as member: view only, and it must not use up the member's pop-up
            if (LoginAs::active()) {
                return false;
            }
            // already adding photos, or in the middle of choosing / paying for a plan
            if (request()->routeIs('photos.*', 'membership.*')) {
                return false;
            }
            if (self::hasPhoto($profile)) {
                return false;
            }

            return Cache::add(self::popupKey($user), true, now()->endOfDay());
        } catch (\Throwable) {
            return false;   // a nudge must never break the page
        }
    }

    private static function barKey(User $user): string
    {
        return 'photo-bar-closed:'.$user->id;
    }

    private static function popupKey(User $user): string
    {
        return 'photo-popup:'.$user->id.':'.now()->toDateString();
    }
}
