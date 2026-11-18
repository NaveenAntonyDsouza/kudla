<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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

    /** Wrong codes allowed per destination before the code is cancelled. */
    public const MAX_WRONG_GUESSES = 5;

    /** How a phone code goes out (phoneSender()). */
    public const SENDER_WHATSAPP = 'whatsapp';
    public const SENDER_SMS = 'sms';

    /**
     * Phone codes cost money per message (WhatsApp ~Rs 0.14, SMS more), and
     * bots ask for codes to run up the bill. Per number: one a minute and
     * PHONE_CODES_PER_HOUR an hour; per site: SiteSetting otp_phone_daily_cap
     * (default PHONE_DAILY_CAP) a day.
     */
    public const PHONE_CODES_PER_HOUR = 5;
    public const PHONE_DAILY_CAP = 200;

    public const LIMIT_WAIT = 'wait';
    public const LIMIT_HOUR = 'hour';
    public const LIMIT_DAY = 'day';

    /**
     * Which way a phone code would go now: WhatsApp when switched on in
     * Settings -> WhatsApp API, else SMS when a Fast2SMS key is set, else
     * none (then nothing can be sent and phone-code sign-in is hidden).
     */
    public static function phoneSender(): ?string
    {
        if (\App\Support\WhatsAppCloud::loginCodesEnabled()) {
            return self::SENDER_WHATSAPP;
        }
        if ((string) config('services.fast2sms.api_key') !== '' || app()->environment('local')) {
            return self::SENDER_SMS; // locally the code is only written to the log
        }

        return null;
    }

    /**
     * Why a phone code can't be sent to this number now (LIMIT_*), or null.
     * Counted per number whether or not it has an account, so the answer
     * never tells anyone that a number is registered.
     */
    public function phoneLimit(string $phone): ?string
    {
        $key = sha1(preg_replace('/\D/', '', $phone));
        if (RateLimiter::tooManyAttempts('otp-phone-min:' . $key, 1)) {
            return self::LIMIT_WAIT;
        }
        if (RateLimiter::tooManyAttempts('otp-phone-hour:' . $key, self::PHONE_CODES_PER_HOUR)) {
            return self::LIMIT_HOUR;
        }
        if ((int) Cache::get(self::dayKey(), 0) >= self::dailyCap()) {
            return self::LIMIT_DAY;
        }

        return null;
    }

    /** Count a request for a phone code against the limits above (also for unknown numbers). */
    public function countPhoneRequest(string $phone): void
    {
        $key = sha1(preg_replace('/\D/', '', $phone));
        RateLimiter::hit('otp-phone-min:' . $key, 60);
        RateLimiter::hit('otp-phone-hour:' . $key, 3600);
    }

    public static function dailyCap(): int
    {
        $cap = (int) SiteSetting::getValue('otp_phone_daily_cap', (string) self::PHONE_DAILY_CAP);

        return $cap > 0 ? $cap : self::PHONE_DAILY_CAP;
    }

    /** Phone codes sent today (IST day), for the settings page. */
    public static function sentToday(): int
    {
        return (int) Cache::get(self::dayKey(), 0);
    }

    private static function dayKey(): string
    {
        return 'otp-phone-day:' . now()->format('Y-m-d');
    }

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

        if ($channel === self::CHANNEL_PHONE) {
            if (self::phoneSender() === null || $this->phoneLimit($destination) !== null) {
                return false;
            }
            $this->countPhoneRequest($destination);
            Cache::add(self::dayKey(), 0, now()->endOfDay());
            Cache::increment(self::dayKey());
        }

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

        // a new code starts with no wrong guesses
        \Illuminate\Support\Facades\RateLimiter::clear('otp-wrong:' . $channel . ':' . sha1(mb_strtolower($destination)));

        if (app()->environment('local')) {
            Log::info("DEV OTP [{$channel}] for {$destination}: {$otp}");

            return true;
        }

        return match ($channel) {
            self::CHANNEL_PHONE => self::phoneSender() === self::SENDER_WHATSAPP
                ? $this->dispatchWhatsApp($destination, $otp)
                : $this->dispatchSms($destination, $otp),
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

        // At most MAX_WRONG_GUESSES wrong codes per destination; then the code is
        // cancelled and a new one must be sent (privacy check, 5 Nov 2026: only a
        // per-address limit applied, so guesses from many addresses never stopped)
        $guesses = 'otp-wrong:' . $channel . ':' . sha1(mb_strtolower($destination));
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($guesses, self::MAX_WRONG_GUESSES)) {
            $record->update(['expires_at' => now()]);

            return false;
        }

        if (! Hash::check($code, $record->otp_code)) {
            \Illuminate\Support\Facades\RateLimiter::hit($guesses, 15 * 60);
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($guesses, self::MAX_WRONG_GUESSES)) {
                $record->update(['expires_at' => now()]);
            }

            return false;
        }

        \Illuminate\Support\Facades\RateLimiter::clear($guesses);
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
     * WhatsApp dispatch (Nov 2026): the approved login_code template. Indian
     * 10-digit numbers get 91 in front. The code itself is never logged or
     * stored in the WhatsApp inbox.
     */
    private function dispatchWhatsApp(string $phone, string $otp): bool
    {
        $to = \App\Support\WhatsApp::digits($phone);
        if ($to === null) {
            return false;
        }

        $result = \App\Support\WhatsAppCloud::sendLoginCode($to, $otp);
        if (! $result['ok']) {
            Log::warning('WhatsApp sign-in code not sent: ' . str_replace($otp, '******', (string) $result['error']));
        }

        return $result['ok'];
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
