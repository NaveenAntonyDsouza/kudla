<?php

namespace App\Support;

use App\Models\Profile;
use Carbon\Carbon;

/**
 * One line about a member for emails: "27 yrs · Udupi · Nurse".
 * No name: emails identify other members by Matri ID.
 */
final class MemberSummary
{
    public static function line(?Profile $profile): string
    {
        if (! $profile) {
            return '';
        }

        $age = $profile->date_of_birth ? (int) Carbon::parse($profile->date_of_birth)->diffInYears(now()) : null;
        $place = $profile->locationInfo?->native_district ?: $profile->locationInfo?->native_state;
        $job = $profile->educationDetail?->occupation;

        return collect([$age ? "{$age} yrs" : null, $place, $job])
            ->filter(fn ($part) => filled($part))
            ->implode(' · ');
    }
}
