<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * "A new version of the app is available" (owner 20 Nov 2026).
 *
 * The site's Android app tags its WebView ("... KudlaMatrimonyApp/1.0.7", Kudla since 1.0.4).
 * When config services.android_app.latest_version is set, a member whose app is older sees a
 * bar with an Update button to Play. An Android WebView without the tag is an app from before
 * the tag (Kudla 1.0.0-1.0.3), so it is older too. "Later" hides the bar for 3 days.
 * Off on every site where the setting is empty. Set it only once that version is live on Play.
 */
final class AppUpdate
{
    public const LATER_COOKIE = 'app_update_later';

    /** The app version in this request, '' for an app from before the tag, null when not our app. */
    public static function versionOf(?string $userAgent): ?string
    {
        $tag = preg_quote((string) config('services.android_app.ua_tag', 'KudlaMatrimonyApp'), '#');
        if ($tag !== '' && preg_match('#\b'.$tag.'/([0-9]+(?:\.[0-9]+){0,3})\b#', (string) $userAgent, $m)) {
            return $m[1];
        }

        return DeviceDetector::isOurApp($userAgent) ? '' : null;
    }

    public static function needed(Request $request): bool
    {
        $latest = trim((string) config('services.android_app.latest_version'));
        if ($latest === '' || trim((string) config('services.android_app.package')) === '' || $request->cookies->has(self::LATER_COOKIE)) {
            return false;
        }
        $version = self::versionOf($request->userAgent());

        return $version !== null && ($version === '' || version_compare($version, $latest, '<'));
    }

    public static function playUrl(): string
    {
        return 'https://play.google.com/store/apps/details?id='.rawurlencode((string) config('services.android_app.package'));
    }
}
