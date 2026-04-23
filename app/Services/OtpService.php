<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * One-time password sending + verification for phone and email.
 *
 * Public API (new in Phase 2a Week 2):
 *   send(string $destination, string $channel): bool
 *   verify(string $destination, string $channel, string $code): bool
 *
 * Backwards-compat wrappers (used by existing web LoginController +
 * RegisterController — DO NOT remove):
 *   sendOtp(string $phone): bool
 *   verifyOtp(string $phone, string $code): bool
 *
 * Dev shortcut: in APP_ENV=local, the OTP is always '123456' and SMS/email
 * dispatch is replaced with a Log::info line so you can read the code from
 * storage/logs/laravel.log.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-02-otp-service-refactor.md
 */
class OtpService
{
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_EMAIL = 'email';

    /**
     * Send an OTP to the given destination on the given channel.
     * Returns true on success (dispatch attempted without exception).
     *
     * Side effects:
     *  - Deletes any existing unverified OTP rows for this (channel, destination)
     *  - Creates a fresh row with hashed code + 10-minute expiry
     *  - Dispatches via Fast2SMS (phone) or Laravel Mail (email) in non-local env
     */
    public function send(string $destination, string $channel = self::CHANNEL_PHONE): bool
    {
        $this->assertValidChannel($channel);

        // Delete any prior unverified OTPs for this channel+destination so a
        // user can always request a fresh code without being blocked by a
        // previous unconsumed one.
        OtpVerification::where('channel', $channel)
            ->where('destination', $destination)
            ->delete();

        $otp = $this->generateOtp();

        OtpVerification::create([
            'phone' => $channel === self::CHANNEL_PHONE ? $destination : null,
            'channel' => $channel,
            'destination' => $destination,
            'otp_code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(config('matrimony.otp_expiry_minutes', 10)),
        ]);

        if (app()->environment('local')) {
            Log::info("DEV OTP [{$channel}] for {$destination}: {$otp}");

            return true;
        }

        return match ($channel) {
            self::CHANNEL_PHONE => $this->dispatchSms($destination, $otp),
            self::CHANNEL_EMAIL => $this->dispatchEmail($destination, $otp),
        };
    }

    /**
     * Verify an OTP. On success, marks the latest matching row as verified
     * and returns true. Returns false for expired, unknown, or wrong codes.
     */
    public function verify(string $destination, string $channel, string $code): bool
    {
        $this->assertValidChannel($channel);

        $record = OtpVerification::where('channel', $channel)
            ->where('destination', $destination)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $record) {
            return false;
        }

        if (! Hash::check($code, $record->otp_code)) {
            return false;
        }

        $record->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Backwards-compat: legacy web LoginController::sendLoginOtp and
     * RegisterController::sendOtp call this. Keep until web is migrated.
     */
    public function sendOtp(string $phone): bool
    {
        return $this->send($phone, self::CHANNEL_PHONE);
    }

    /**
     * Backwards-compat: legacy verifyOtp(phone, code) — delegates to verify().
     */
    public function verifyOtp(string $phone, string $code): bool
    {
        return $this->verify($phone, self::CHANNEL_PHONE, $code);
    }

    private function assertValidChannel(string $channel): void
    {
        if (! in_array($channel, [self::CHANNEL_PHONE, self::CHANNEL_EMAIL], true)) {
            throw new InvalidArgumentException("Invalid OTP channel: {$channel}");
        }
    }

    private function generateOtp(): string
    {
        return app()->environment('local') ? '123456' : (string) random_int(100000, 999999);
    }

    /**
     * Fast2SMS dispatch — unchanged byte-for-byte from the pre-Week-2
     * implementation. Called for channel='phone' in non-local env.
     */
    private function dispatchSms(string $phone, string $otp): bool
    {
        try {
            $response = Http::withHeaders([
                'authorization' => config('services.fast2sms.api_key'),
            ])->post('https://www.fast2sms.com/dev/bulkV2', [
                'route' => 'otp',
                'variables_values' => $otp,
                'numbers' => $phone,
                'flash' => '0',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Fast2SMS OTP failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Email OTP dispatch via Laravel Mail. Uses raw Mail to avoid coupling
     * to the DatabaseMailable pattern (this is a system-level email; we
     * don't want admins editing the template and accidentally breaking it).
     */
    private function dispatchEmail(string $email, string $otp): bool
    {
        try {
            $siteName = SiteSetting::getValue('site_name', config('app.name'));
            $expiryMinutes = config('matrimony.otp_expiry_minutes', 10);

            Mail::raw(
                "Your {$siteName} verification code is: {$otp}\n\nThis code expires in {$expiryMinutes} minutes.\n\nIf you did not request this, ignore this email.",
                function ($message) use ($email, $siteName) {
                    $message->to($email)->subject("Verification code - {$siteName}");
                }
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Email OTP dispatch failed: '.$e->getMessage());

            return false;
        }
    }
}
