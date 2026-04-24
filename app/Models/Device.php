<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * A single registered Flutter client (phone/tablet). Owned by a user, and
 * optionally linked to the Sanctum token that was active when the device
 * was registered.
 *
 * The FCM token is the primary dispatch target — NotificationService
 * reads `devices.where(user=X, is_active=true)` to decide who to push to.
 *
 * When a Sanctum token is revoked (logout or password reset), the link
 * is set to NULL by the FK ON DELETE SET NULL. The device row itself
 * stays around — its is_active flag gets cleared when the FCM token
 * itself becomes invalid (detected on next push attempt).
 */
class Device extends Model
{
    protected $fillable = [
        'user_id',
        'personal_access_token_id',
        'fcm_token',
        'platform',
        'device_model',
        'app_version',
        'os_version',
        'locale',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class);
    }
}
