<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * The site's WhatsApp number (SiteSetting `whatsapp`) as a working wa.me link.
 *
 * wa.me needs the number with its country code; admins often save Indian
 * numbers as 10 digits ("77600 20186"), which made every WhatsApp link on
 * kudla open an error. Placeholder numbers ("+91 00000 00000") count as none.
 */
final class WhatsApp
{
    /** International digits for wa.me, or null when no usable number is set. */
    public static function digits(?string $number = null): ?string
    {
        $digits = preg_replace('/\D/', '', (string) ($number ?? SiteSetting::getValue('whatsapp', '')));

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);            // 0 77600 20186
        }
        if (strlen($digits) === 10 && preg_match('/^[6-9]/', $digits)) {
            $digits = '91'.$digits;                  // Indian mobile without country code
        }
        if (strlen($digits) < 11 || trim(substr($digits, 2), '0') === '') {
            return null;                             // too short, or a 00000 placeholder
        }

        return $digits;
    }

    public static function link(?string $text = null): ?string
    {
        $digits = self::digits();
        if ($digits === null) {
            return null;
        }

        return 'https://wa.me/'.$digits.($text !== null && $text !== '' ? '?text='.rawurlencode($text) : '');
    }

    /** The floating chat button: switched on for this site and a real number set. */
    public static function buttonEnabled(): bool
    {
        try {
            return SiteSetting::getValue('whatsapp_button_enabled', '0') === '1' && self::digits() !== null;
        } catch (\Throwable) {
            return false; // a page decoration must never break the page
        }
    }

    /**
     * Opening message for the chat button: what the member is looking at and,
     * when logged in, their member ID — so staff can help without asking.
     */
    public static function prefilledMessage(Request $request): string
    {
        $site = (string) SiteSetting::getValue('site_name', config('app.name'));
        $route = (string) $request->route()?->getName();

        $topic = match (true) {
            $route === 'register', str_starts_with($route, 'register.') => 'I need help with registration.',
            str_starts_with($route, 'onboarding.') => 'I need help completing my profile.',
            str_starts_with($route, 'membership.') => 'I have a question about membership plans.',
            $route === 'profile.view' && ($viewed = self::viewedMatriId($request)) => "I'd like to know more about profile {$viewed}.",
            default => 'I have a question.',
        };

        $matriId = $request->user()?->profile?->matri_id;
        $who = $matriId ? ' My '.LoginIdentifier::memberIdLabel().": {$matriId}." : '';

        return "Hi {$site}, {$topic}{$who}";
    }

    private static function viewedMatriId(Request $request): ?string
    {
        foreach ((array) $request->route()?->parameters() as $value) {
            $id = is_object($value) ? ($value->matri_id ?? null) : (is_string($value) ? $value : null);
            if (is_string($id) && preg_match('/^[A-Z]{1,5}\d{3,}$/i', $id)) {
                return strtoupper($id);
            }
        }

        return null;
    }
}
