<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoPrivacySetting extends Model
{
    protected $fillable = [
        'profile_id',
        'privacy_level',
        'show_profile_photo',
        'show_album_photos',
        'show_family_photos',
    ];

    protected function casts(): array
    {
        return [
            'show_profile_photo' => 'boolean',
            'show_album_photos' => 'boolean',
            'show_family_photos' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
