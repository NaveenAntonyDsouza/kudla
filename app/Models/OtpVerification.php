<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One-time passwords for phone + email verification.
 *
 * Starting Phase 2a Week 2, the same table serves both channels:
 *   channel='phone', destination=<phone>  (legacy, web + mobile)
 *   channel='email', destination=<email>  (new, mobile API)
 *
 * `phone` column stays (nullable) for backwards-compat with existing web
 * queries. New writes should populate `destination` + `channel` regardless
 * of channel.
 */
class OtpVerification extends Model
{
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_EMAIL = 'email';

    protected $fillable = [
        'phone',
        'otp_code',
        'channel',
        'destination',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
