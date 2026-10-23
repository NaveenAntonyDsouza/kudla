<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use Illuminate\Support\Str;

/**
 * The picture Facebook, WhatsApp and X show for a shared link (og:image,
 * twitter:image, the JSON-LD logo).
 *
 * Until Oct 2026 the layout printed the header logo's RELATIVE path
 * (/storage/branding/…), which link crawlers ignore, so shared links showed
 * no picture. The header logo is also a poor preview: often a transparent
 * PNG with dark lettering, unreadable on a dark-mode preview. A site can now
 * set its own share image (Theme & Branding → Share image, 1200×630 on a
 * solid background), used before the header logo.
 */
class ShareImage
{
    public const SETTING = 'share_image_url';

    /**
     * The first image given (per-page SEO image, the page's own picture such
     * as a blog cover), else the site's share image, else the header logo.
     * Always an absolute URL, or null when the site has none of them.
     */
    public static function for(?string ...$pageImages): ?string
    {
        $candidates = [...$pageImages, self::siteImage(), self::logo()];

        foreach ($candidates as $image) {
            if (filled($image)) {
                return self::absolute($image);
            }
        }

        return null;
    }

    /** The site's own share image, as stored (e.g. /storage/branding/share.png). */
    public static function siteImage(): ?string
    {
        return SiteSetting::getValue(self::SETTING) ?: null;
    }

    /** The header logo as an absolute URL (Google wants one for the Organization logo). */
    public static function logo(): ?string
    {
        return self::absolute(ThemeSetting::getTheme()->logo_url ?? null);
    }

    /**
     * X shows a wide preview only for card type summary_large_image; the
     * header logo is a small wordmark, so it keeps the small "summary" card.
     */
    public static function twitterCard(?string $image): string
    {
        return $image && $image !== self::logo() ? 'summary_large_image' : 'summary';
    }

    /** "/storage/x.png" → "https://site.com/storage/x.png"; full http(s) URLs are left alone. */
    public static function absolute(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:' . $url;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($url, '/');
    }
}
