<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IgnoredProfile extends Model
{
    protected $fillable = [
        'profile_id',
        'ignored_profile_id',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function ignoredProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'ignored_profile_id');
    }
}
