<?php

namespace App\Support;

/**
 * The note shown after a member taps "Resend OTP" — before this, the page
 * simply reloaded and members couldn't tell whether a new code had gone out.
 * Controllers flash it as `otp_resent` only when the form posts resend=1
 * (the first send already says "enter the OTP you received").
 */
final class OtpResendNotice
{
    public static function email(string $email): string
    {
        return "We've sent a new code to {$email}. It can take a minute to arrive — please check your spam folder too.";
    }

    public static function phone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $ending = $digits !== '' ? ' ending ' . substr($digits, -4) : '';

        return "We've sent a new code to your mobile number{$ending}. It can take a minute to arrive.";
    }
}
