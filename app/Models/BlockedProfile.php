<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedProfile extends Model
{
    protected $fillable = [
        'profile_id',
        'blocked_profile_id',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function blockedProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'blocked_profile_id');
    }
}
