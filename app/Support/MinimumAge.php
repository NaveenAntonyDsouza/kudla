<?php

namespace App\Support;

use App\Models\SiteSetting;
use Carbon\Carbon;

/**
 * Minimum age to register, per gender (Oct 2026). In India the legal age to
 * marry is 21 for men and 18 for women, so those are the defaults; a site
 * for another country can change them in Admin → Site Settings →
 * Registration & Approval (min_age_male / min_age_female).
 */
class MinimumAge
{
    public const DEFAULT_MALE = 21;
    public const DEFAULT_FEMALE = 18;

    public static function forGender(?string $gender): int
    {
        return match (strtolower((string) $gender)) {
            'male' => self::setting('min_age_male', self::DEFAULT_MALE),
            'female' => self::setting('min_age_female', self::DEFAULT_FEMALE),
            default => self::lowest(),
        };
    }

    /** The younger of the two limits — used before a gender is chosen. */
    public static function lowest(): int
    {
        return min(self::forGender('male'), self::forGender('female'));
    }

    /** Latest allowed date of birth, Y-m-d (for a date input's max). */
    public static function latestBirthDate(?string $gender = null): string
    {
        return now()->subYears(self::forGender($gender))->format('Y-m-d');
    }

    public static function isOldEnough(Carbon|string $dateOfBirth, ?string $gender): bool
    {
        $dob = $dateOfBirth instanceof Carbon ? $dateOfBirth : Carbon::parse($dateOfBirth);

        return $dob->lte(now()->subYears(self::forGender($gender)));
    }

    public static function message(?string $gender): string
    {
        $g = strtolower((string) $gender);
        if (self::forGender('male') === self::forGender('female') || ! in_array($g, ['male', 'female'], true)) {
            return 'You must be at least ' . self::forGender($g) . ' years old to register.';
        }

        return ($g === 'male' ? 'Men' : 'Women') . ' must be at least ' . self::forGender($g) . ' years old to register.';
    }

    private static function setting(string $key, int $default): int
    {
        try {
            $v = (int) SiteSetting::getValue($key, (string) $default);
        } catch (\Throwable) {
            return $default; // no settings table yet (fresh install / some tests)
        }

        return $v >= 18 && $v <= 30 ? $v : $default;
    }
}
