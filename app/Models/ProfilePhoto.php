<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    // URL accessors

    public function getFullUrlAttribute(): string
    {
        return $this->photo_url ? Storage::disk('public')->url($this->photo_url) : '';
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->thumbnail_url ? Storage::disk('public')->url($this->thumbnail_url) : $this->full_url;
    }

    // Scopes

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('photo_type', $type);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    // Relationships

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    // Count limits per type

    public static function maxForType(string $type): int
    {
        return match ($type) {
            'profile' => 1,
            'album' => 9,
            'family' => 3,
            default => 0,
        };
    }
}
