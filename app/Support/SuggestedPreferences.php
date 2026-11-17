<?php

namespace App\Support;

use App\Models\PartnerPreference;
use App\Models\Profile;

/**
 * Partner preferences to match on when a member has never saved their own
 * (owner, 17 Nov 2026). 430 of Kudla's 848 live members had none, so My Matches,
 * Mutual Matches, the dashboard's Recommended and the weekly match email were all
 * empty for them, and they never showed in anyone else's Mutual Matches.
 *
 * Built from the member's own profile and never saved, so their preferences stay
 * empty until they choose themselves:
 *  - age: a man, women from 7 years younger up to his own age;
 *         a woman, men from her own age up to 7 years older (men 21 and over)
 *  - religion: their own
 *  - height (owner's rule): a man, every height up to and including his own;
 *         a woman, her own height and every height above it
 *
 * Only for members with NO saved preferences: a member who saved everything as
 * "Any" chose that. Preferences only rank matches, they never hide anyone.
 *
 * Switch: Settings → Site Settings → Member site → "Matches for members without preferences".
 */
class SuggestedPreferences
{
    public const AGE_GAP = 7;

    /** The member's saved preferences; or, with the switch on, ones suggested from their profile; or null. */
    public static function for(Profile $profile): ?PartnerPreference
    {
        $saved = $profile->partnerPreference;
        if ($saved) {
            return $saved;
        }

        try {
            return AdminModules::suggestedPreferences() ? self::suggest($profile) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Suggested rather than chosen by the member (never saved). */
    public static function isSuggested(?PartnerPreference $prefs): bool
    {
        return $prefs !== null && ! $prefs->exists;
    }

    public static function suggest(Profile $profile): ?PartnerPreference
    {
        $gender = strtolower((string) $profile->gender);
        if (! in_array($gender, ['male', 'female'], true)) {
            return null;
        }
        $male = $gender === 'male';
        $attributes = [];

        $age = (int) $profile->age;
        if ($age >= 18) {
            [$from, $to] = $male
                ? [max(18, $age - self::AGE_GAP), $age]
                : [max(21, $age), max(21, $age) + self::AGE_GAP];
            $attributes['age_from'] = $from;
            $attributes['age_to'] = $to;
        }

        $religion = $profile->religiousInfo?->religion;
        if ($religion) {
            $attributes['religions'] = [$religion];
        }

        // heights are stored as "170 cm - 5 ft 07 inch" and compared by the number
        $height = (int) $profile->height;
        if ($height > 0) {
            [$lowest, $tallest] = self::heightRange();
            $attributes['height_from_cm'] = (string) ($male ? $lowest : $height);
            $attributes['height_to_cm'] = (string) ($male ? $height : $tallest);
        }

        return $attributes === [] ? null : new PartnerPreference($attributes);
    }

    /** The shortest and tallest heights members can choose. */
    private static function heightRange(): array
    {
        $heights = array_filter(array_map('intval', (array) config('reference_data.height_list', [])));

        return $heights === [] ? [134, 213] : [min($heights), max($heights)];
    }
}
