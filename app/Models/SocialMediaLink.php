<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaLink extends Model
{
    protected $fillable = [
        'profile_id',
        'instagram_url',
        'facebook_url',
        'linkedin_url',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
