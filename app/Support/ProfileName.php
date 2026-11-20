<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\User;

/**
 * How another member's name is shown on the member site and in the app (owner 20 Nov 2026, like Chavara):
 * free members see "Priya D.", paid members see the full name. A member always sees their own full name.
 * Signed-out visitors (the public home page) always get "Priya D.". The switch is
 * Site Settings -> Member site -> "Full names for paid members only" (AdminModules::fullNamePaidOnly).
 */
final class ProfileName
{
    /** The name to print for $target, as seen by $viewer (default: the signed-in member). */
    public static function for(?Profile $target, User|Profile|null $viewer = null): string
    {
        if (! $target) {
            return '';
        }

        return self::seesFull($target, $viewer) ? (string) $target->full_name : MemberName::short($target->full_name);
    }

    /** True when $viewer may see $target's full name. */
    public static function seesFull(Profile $target, User|Profile|null $viewer = null): bool
    {
        $user = self::user($viewer);
        if (! $user) {
            return false;                                  // signed out: never the surname
        }
        if ((int) $user->id === (int) $target->user_id) {
            return true;
        }

        return self::viewerSeesFullNames($user);
    }

    /** True when the viewer sees every member's full name (paid, free-membership mode, or the switch is off). */
    public static function viewerSeesFullNames(User|Profile|null $viewer = null): bool
    {
        $user = self::user($viewer);
        if (! $user) {
            return false;
        }
        // isPremium() is a query; lists show many names, so remember it for this request
        $key = 'profile_name_full:' . $user->id;
        $attrs = request()->attributes;
        if (! $attrs->has($key)) {
            try {
                $attrs->set($key, ! AdminModules::fullNamePaidOnly() || $user->isPremium());
            } catch (\Illuminate\Database\QueryException $e) {
                $attrs->set($key, false);                // can't tell: keep the surname back
            }
        }

        return (bool) $attrs->get($key);
    }

    /** True when the page should offer "Upgrade to see the full name" for $target. */
    public static function hidden(?Profile $target, User|Profile|null $viewer = null): bool
    {
        return $target && filled($target->full_name) && ! self::seesFull($target, $viewer)
            && mb_strtolower(MemberName::short($target->full_name)) !== mb_strtolower(trim((string) $target->full_name));
    }

    private static function user(User|Profile|null $viewer): ?User
    {
        if ($viewer instanceof Profile) {
            return $viewer->user;
        }

        return $viewer ?? auth()->user();
    }
}
