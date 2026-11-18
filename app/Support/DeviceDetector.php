<?php

namespace App\Support;

/**
 * Tiny user-agent → device-type classifier.
 *
 * Single source of truth for the heuristic shared by:
 *   - profiles.registration_source (where a member signed up)
 *   - LoginHistory device_type accessor (where a session came from)
 *
 * Deliberately a small string-match heuristic, not a full UA-parsing
 * library — we only need a coarse Desktop / Mobile / Tablet bucket,
 * and pulling in a dependency (mobile-detect, jenssegers/agent) for
 * three str_contains checks isn't worth the weight.
 *
 * Native app registrations don't go through this — the mobile API
 * sets registration_source = 'App' directly (the API is the app).
 *
 * Our Android apps on Play (Kudla, Kannada Dating) are WebView wrappers of
 * the website, so they arrive here with a browser user agent. Android's
 * WebView marks itself "; wv)" — counted as 'App' since 18 Nov 2026 (the
 * Kannada Dating app brought 74 of 77 sign-ups in a month, and they were
 * recorded as plain 'Mobile'). Facebook, Instagram, the Google app and
 * other apps' in-app browsers are WebViews too; their markers are excluded.
 */
class DeviceDetector
{
    public const DESKTOP = 'Desktop';
    public const MOBILE = 'Mobile';
    public const TABLET = 'Tablet';
    public const APP = 'App';
    public const ADMIN = 'Admin';

    /**
     * Classify a user-agent string into Desktop / Mobile / Tablet.
     * Falls back to Desktop for empty/unknown UAs (the safe default —
     * a missing UA is far more likely a desktop bot/curl than a phone).
     */
    public static function type(?string $userAgent): string
    {
        $ua = strtolower($userAgent ?? '');

        if (self::isOurApp($ua)) {
            return self::APP;
        }

        // Tablet check first — iPads/tablets often also match 'mobile'.
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return self::TABLET;
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return self::MOBILE;
        }

        return self::DESKTOP;
    }

    /** Android WebView ("; wv)") that is not another app's in-app browser. */
    public static function isOurApp(?string $userAgent): bool
    {
        $ua = strtolower($userAgent ?? '');

        return str_contains($ua, '; wv)')
            && ! preg_match('/fban|fbav|fb_iab|instagram|gsa\/|line\/|snapchat|twitter|linkedinapp|micromessenger|pinterest|musical_ly|bytedancewebview/', $ua);
    }
}
