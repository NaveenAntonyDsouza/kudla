<?php

namespace App\Services;

class OtpService
{
    /**
     * Send OTP to the given phone number.
     *
     * @return bool Whether the OTP was sent successfully
     */
    public function sendOtp(string $phone): bool
    {
        // TODO: Implement in Phase 2
        throw new \RuntimeException('OtpService::sendOtp() not yet implemented.');
    }

    /**
     * Verify the OTP code for the given phone number.
     *
     * @return bool Whether the OTP is valid
     */
    public function verifyOtp(string $phone, string $code): bool
    {
        // TODO: Implement in Phase 2
        throw new \RuntimeException('OtpService::verifyOtp() not yet implemented.');
    }
}
