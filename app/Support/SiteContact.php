<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\ThemeSetting;

/**
 * Contact details and footer colour shared by the headers and footers
 * (Oct 2026: phone strip for visitors, number in the sign-up header, contact
 * details and app links in the footer, footer in the brand's deep colour).
 */
class SiteContact
{
    /** International digits of the site phone (91…), or null. */
    public static function phoneDigits(): ?string
    {
        return WhatsApp::digits(SiteSetting::getValue('phone', ''));
    }

    /** "+91 77600 20186" style, or ''. */
    public static function phoneDisplay(): string
    {
        $d = self::phoneDigits();
        if (! $d) {
            return '';
        }

        return strlen($d) === 12 && str_starts_with($d, '91')
            ? '+91 ' . substr($d, 2, 5) . ' ' . substr($d, 7)
            : '+' . $d;
    }

    public static function phoneHref(): ?string
    {
        $d = self::phoneDigits();

        return $d ? 'tel:+' . $d : null;
    }

    public static function whatsappHref(): ?string
    {
        return WhatsApp::link();
    }

    public static function email(): string
    {
        return trim((string) SiteSetting::getValue('email', ''));
    }

    public static function address(): string
    {
        return trim((string) SiteSetting::getValue('address', ''));
    }

    /** Site setting show_not_dating_note (default on; off on dating-style sites). */
    public static function showNotDatingNote(): bool
    {
        return SiteSetting::getValue('show_not_dating_note', '1') === '1';
    }

    /** Tagline followed by a full stop, without doubling "!" "." "?" at its end. */
    public static function sentence(?string $text): string
    {
        $text = trim((string) $text);

        return $text === '' || preg_match('/[.!?।]$/u', $text) ? $text : $text . '.';
    }

    /** Footer background: the theme's main colour deepened almost to black. */
    public static function footerColour(): string
    {
        $primary = ThemeSetting::getTheme()->primary_color ?? '';
        if (! preg_match('/^#[0-9a-f]{6}$/i', (string) $primary)) {
            return '#111827';
        }
        [$r, $g, $b] = sscanf(strtolower($primary), '#%02x%02x%02x');

        return sprintf('#%02x%02x%02x', (int) round($r * 0.26 + 12 * 0.74), (int) round($g * 0.26 + 4 * 0.74), (int) round($b * 0.26 + 18 * 0.74));
    }
}
