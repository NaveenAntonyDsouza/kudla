<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilePhoto extends Model
{
    protected $fillable = [
        'profile_id',
        'photo_type',
        'photo_url',
        'cloudinary_public_id',
        'thumbnail_url',
        'is_primary',
        'is_visible',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_visible' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('photo_type', $type);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
