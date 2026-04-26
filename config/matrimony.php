<?php

return [
    'id_prefix' => env('MATRI_ID_PREFIX', 'AM'),
    'daily_interest_limit_free' => 5,
    'otp_expiry_minutes' => 10,
    'otp_max_attempts' => 5,
    'otp_cooldown_seconds' => 30,
    'max_profile_photos' => 1,
    'max_album_photos' => 9,
    'max_family_photos' => 3,
    // Reduced from 30 → 5 MB (Cat 5 audit). Web PhotoController hardcoded
    // 5 MB and was the de-facto limit anyway. 5 MB after server-side
    // optimization yields plenty of resolution while keeping disk + CDN
    // costs predictable for buyers on a per-photo billing model.
    'max_photo_size_mb' => 5,
    'cancel_interest_window_hours' => 24,
    'resend_interest_cooldown_days' => 30,
    'password_min_length' => 6,
    'password_max_length' => 14,
    'registration_min_age' => 18,
];
