<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step a member took towards paying, or one time a free member was stopped by a
 * paid feature (18 Nov 2026). Written by App\Support\MemberEvents.
 */
class MemberEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'event', 'subject', 'data', 'created_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
