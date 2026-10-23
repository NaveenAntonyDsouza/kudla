<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * How tall the site logo shows in the website header (main, sign-up and
 * onboarding layouts). Each site sets it in Theme & Branding, because logos
 * differ: a wide one-line logo suits 40 px, a compact stacked one needs more.
 *
 * Oct 2026: kudlamatrimony.com switched to a stacked logo (Kudla over
 * Matrimony.com), which looked small at the old fixed 40 px (Tailwind h-10).
 * Printed as an inline style because the live CSS build is not rebuilt on
 * deploy, so new utility classes would not exist there.
 */
class HeaderLogo
{
    public const SETTING = 'header_logo_height';

    public const DEFAULT = 40;

    public const MIN = 24;

    public const MAX = 60;   // the header bar is 64 px tall

    public static function height(): int
    {
        return self::clamp(SiteSetting::getValue(self::SETTING, self::DEFAULT));
    }

    public static function clamp(mixed $value): int
    {
        if (! is_numeric($value)) {
            return self::DEFAULT;
        }

        return max(self::MIN, min(self::MAX, (int) round((float) $value)));
    }
}
