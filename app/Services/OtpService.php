<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Send OTP to phone number.
     * In local dev (APP_ENV=local), OTP is always 123456 and no SMS is sent.
     */
    public function sendOtp(string $phone): bool
    {
        // Delete old OTPs for this phone
        OtpVerification::where('phone', $phone)->delete();

        // Generate OTP
        $otp = app()->environment('local') ? '123456' : (string) random_int(100000, 999999);

        // Store hashed OTP
        OtpVerification::create([
            'phone' => $phone,
            'otp_code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(config('matrimony.otp_expiry_minutes', 10)),
        ]);

        // In local dev, log the OTP instead of sending SMS
        if (app()->environment('local')) {
            Log::info("DEV OTP for {$phone}: {$otp}");

            return true;
        }

        // Production: send via Fast2SMS
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
     * Verify OTP code for phone number.
     */
    public function verifyOtp(string $phone, string $code): bool
    {
        $record = OtpVerification::where('phone', $phone)
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
}
