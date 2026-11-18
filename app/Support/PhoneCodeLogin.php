<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OtpService;

/**
 * Signing in with a one-time code sent to the member's phone (Nov 2026):
 * by WhatsApp when Settings -> WhatsApp API "Send sign-in codes" is on,
 * else by SMS when a Fast2SMS key is set. Shared by the login page, the
 * web LoginController and the app API.
 *
 * Until Nov 2026 the "Mobile OTP" tab showed on sites with no SMS key, so
 * codes never arrived; it now shows only when a code can really be sent.
 */
final class PhoneCodeLogin
{
    /** Code requests allowed per visitor (IP) an hour, on top of the per-number limits. */
    public const PER_VISITOR_PER_HOUR = 10;

    public static function available(): bool
    {
        return SiteSetting::getValue('mobile_otp_login_enabled', '1') === '1'
            && OtpService::phoneSender() !== null;
    }

    public static function byWhatsApp(): bool
    {
        return OtpService::phoneSender() === OtpService::SENDER_WHATSAPP;
    }

    /** Tab label on the login page. */
    public static function label(): string
    {
        return self::byWhatsApp() ? 'WhatsApp Code' : 'Mobile OTP';
    }

    /**
     * The member who may sign in with this number: a member account (never
     * staff or admin, who use the admin login with two-step), not blocked.
     * Null for anything else; callers must answer the same way either way.
     */
    public static function memberFor(string $phone): ?User
    {
        $user = User::where('phone', $phone)
            ->whereNull('staff_role_id')
            ->where(fn ($q) => $q->whereNull('role')->orWhere('role', '!=', 'admin'))
            ->first();

        return $user && $user->blockedStatus() === null ? $user : null;
    }

    /**
     * Where a WhatsApp code goes: the WhatsApp number on the member's profile
     * when it is a usable number (17% of Kudla members, Nov 2026, use a
     * different number for WhatsApp than for logging in), else the login mobile.
     */
    public static function deliverTo(User $user): string
    {
        $whatsapp = WhatsApp::digits((string) $user->profile?->contactInfo?->whatsapp_number);

        return $whatsapp ?? (string) $user->phone;
    }

    public static function limitMessage(string $limit): string
    {
        return match ($limit) {
            OtpService::LIMIT_WAIT => 'A code was sent a moment ago. Please wait a minute before asking for another one.',
            OtpService::LIMIT_HOUR => 'Too many codes asked for this number. Please try again in an hour, or log in with your password.',
            default => 'Sign-in codes are not available right now. Please log in with your password or email.',
        };
    }

    /** What the page says after "Send code", the same whether or not the number has an account. */
    public static function sentMessage(string $phone): string
    {
        return self::byWhatsApp()
            ? "If +91 {$phone} belongs to an account here, we've sent a 6-digit code to the WhatsApp number on that account."
            : "If +91 {$phone} belongs to an account here, we've sent a 6-digit code to it by SMS.";
    }
}
