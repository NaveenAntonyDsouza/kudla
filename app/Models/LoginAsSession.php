<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One "Login as member" visit by a staff account (App\Support\LoginAs).
 * Written by the site only; nothing in the admin edits or deletes these.
 */
class LoginAsSession extends Model
{
    public const ENDED = [
        'exit' => 'Staff pressed Exit',
        'expired' => 'Time limit reached',
        'staff_signed_out' => 'Staff signed out of admin',
        'member_changed' => 'Another sign-in took over',
        'replaced' => 'Staff opened another member',
    ];

    protected $fillable = [
        'staff_user_id',
        'member_user_id',
        'matri_id',
        'reason',
        'ip_address',
        'user_agent',
        'started_at',
        'expires_at',
        'ended_at',
        'ended_how',
        'pages_opened',
        'blocked_actions',
        'pages',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
            'pages_opened' => 'integer',
            'blocked_actions' => 'integer',
            'pages' => 'array',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    /** Minutes the visit lasted (to now while it is still open). */
    public function getMinutesAttribute(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        return (int) max(0, round($this->started_at->diffInSeconds($this->ended_at ?? now()) / 60));
    }
}
