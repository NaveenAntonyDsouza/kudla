<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The "last seen" line on profile cards (weekly review, 16 Nov 2026: interests
 * sent to members who had not been on the site for a month went unanswered, and
 * the card said nothing at all about members never seen).
 *
 * users.last_login_at was only written by the sign-in form until
 * TrackMemberActivity went live on 2 Oct 2026; registration and remembered
 * sessions left it empty. So an empty value means "not seen since 2 Oct 2026"
 * for a member who joined before that day, and "never signed in" only for one
 * who joined after it (e.g. a profile staff typed in on the console).
 *
 * Switch (off by default; on for Kudla only, owner 18 Nov 2026): Settings → Site Settings → Member site → "Last seen on profiles". Used by the
 * profile card and the full profile (profile/preview). Off, both keep their old lines:
 * the card "12d ago" (nothing when never seen), the profile "Last Login : <date>".
 */
class LastSeen
{
    public const TRACKED_FROM = '2026-10-02';

    /**
     * [label, dot class, text class], or null for no line. The classes are ones
     * the card already used, so they exist in the prebuilt CSS.
     */
    public static function forCard(?CarbonInterface $lastLogin, ?CarbonInterface $joinedAt): ?array
    {
        if (! AdminModules::lastSeen()) {
            return self::legacy($lastLogin);
        }

        $now = now();
        $grey = ['bg-gray-300', 'text-gray-500'];

        if (! $lastLogin) {
            $from = Carbon::parse(self::TRACKED_FROM);
            if ($joinedAt && $joinedAt->greaterThanOrEqualTo($from)) {
                return ['Not signed in yet', ...$grey];
            }

            return ['Last seen '.self::over($from, $now), ...$grey];
        }

        $hours = (int) $lastLogin->diffInHours($now);
        if ($hours < 1) {
            return ['Online now', 'bg-green-500', 'text-green-700'];
        }
        if ($hours < 24) {
            return ['Active today', 'bg-green-400', 'text-green-600'];
        }

        $colour = match (true) {
            $hours < 72 => ['bg-yellow-400', 'text-yellow-700'],
            $hours < 168 => ['bg-orange-400', 'text-orange-700'],
            default => $grey,
        };

        return ['Last seen '.self::ago($lastLogin, $now), ...$colour];
    }

    private static function ago(CarbonInterface $then, CarbonInterface $now): string
    {
        $days = (int) $then->diffInDays($now);
        if ($days <= 1) {
            return 'yesterday';
        }
        if ($days < 30) {
            return $days.' days ago';
        }

        $months = max(1, (int) $then->diffInMonths($now));
        if ($months < 12) {
            return $months === 1 ? 'a month ago' : $months.' months ago';
        }

        return 'over a year ago';
    }

    /** Not seen since tracking began: all we know is that it was before then. */
    private static function over(CarbonInterface $from, CarbonInterface $now): string
    {
        $months = (int) $from->diffInMonths($now);
        if ($months < 1) {
            return 'over '.max(1, (int) $from->diffInDays($now)).' days ago';
        }
        if ($months >= 12) {
            return 'over a year ago';
        }

        return $months === 1 ? 'over a month ago' : 'over '.$months.' months ago';
    }

    /** The card's line before 16 Nov 2026, kept exactly while the switch is off. */
    private static function legacy(?CarbonInterface $lastLogin): ?array
    {
        if (! $lastLogin) {
            return null;
        }

        $hours = $lastLogin->diffInHours(now());
        $days = (int) $lastLogin->diffInDays(now()).'d ago';

        return match (true) {
            $hours < 1 => ['Online now', 'bg-green-500', 'text-green-700'],
            $hours < 24 => ['Today', 'bg-green-400', 'text-green-600'],
            $hours < 72 => [$days, 'bg-yellow-400', 'text-yellow-700'],
            $hours < 168 => [$days, 'bg-orange-400', 'text-orange-700'],
            default => [$days, 'bg-gray-300', 'text-gray-500'],
        };
    }
}
