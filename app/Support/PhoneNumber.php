<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Member phone numbers on sign-up (24 Nov 2026, for a worldwide app-only site).
 *
 * Off (every Indian site, the default): a 10-digit Indian mobile, stored as typed ("9876543210"),
 * exactly as before.
 * On (Site Settings → `international_phones`): any country. The number is stored WITH its country
 * code in international form ("+447700900123"), from the dial-code picker the phone field already
 * has (`phone_code`, e.g. "+44") or a number typed with "+"/"00". The staff call list, WhatsApp links
 * and sign-in by mobile number already work with numbers stored this way.
 */
final class PhoneNumber
{
    public const SETTING = 'international_phones';

    public static function international(): bool
    {
        try {
            return SiteSetting::getValue(self::SETTING, '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * "+44" + "07700 900123" → "+447700900123". A number typed with "+" or "00" keeps its own code.
     * Null when it cannot be a phone number (too short or too long for the international format).
     */
    public static function international164(?string $code, ?string $number): ?string
    {
        $number = trim((string) $number);
        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '+') || str_starts_with($number, '00')) {
            $digits = preg_replace('/\D/', '', $number);
            if (str_starts_with($number, '00')) {
                $digits = substr($digits, 2);
            }
        } else {
            $cc = preg_replace('/\D/', '', (string) $code);
            if ($cc === '') {
                $cc = preg_replace('/\D/', '', (string) self::defaultCode());
            }
            $national = ltrim(preg_replace('/\D/', '', $number), '0');   // drop the "0" dialled inside a country
            if (strlen($national) < 4) {
                return null;
            }
            $digits = $cc.$national;
        }

        return preg_match('/^[1-9]\d{7,14}$/', $digits) ? '+'.$digits : null;
    }

    /** Turn the typed number into the stored form before validation (only when the setting is on). */
    public static function prepare(Request $request, string $field = 'phone'): void
    {
        if (! self::international()) {
            return;
        }
        $full = self::international164($request->input($field.'_code'), $request->input($field));
        if ($full !== null) {
            $request->merge([$field => $full]);
        }
    }

    /** Validation for the member's phone on sign-up; $ignoreUserId = the member editing their own step 1. */
    public static function rules(?int $ignoreUserId = null): array
    {
        $unique = Rule::unique('users', 'phone');
        if ($ignoreUserId) {
            $unique = $unique->ignore($ignoreUserId);
        }

        return self::international()
            ? ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/', $unique]
            : ['required', 'digits:10', $unique];
    }

    /** Messages for rules(). */
    public static function messages(string $field = 'phone'): array
    {
        return [
            $field.'.regex' => 'Please enter a valid mobile number and choose its country code.',
            $field.'.digits' => 'Please enter a 10-digit mobile number.',
        ];
    }

    public static function defaultCode(): string
    {
        try {
            return (string) (SiteSetting::getValue('default_phone_code', '+91') ?: '+91');
        } catch (\Throwable) {
            return '+91';
        }
    }
}
