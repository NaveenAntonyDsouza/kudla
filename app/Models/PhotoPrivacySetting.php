<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoPrivacySetting extends Model
{
    public const LEVEL_VISIBLE_TO_ALL = 'visible_to_all';
    public const LEVEL_INTEREST_ACCEPTED = 'interest_accepted';
    public const LEVEL_HIDDEN = 'hidden';

    public const LEVELS = [
        self::LEVEL_VISIBLE_TO_ALL => 'Visible to all',
        self::LEVEL_INTEREST_ACCEPTED => 'Only after interest accepted',
        self::LEVEL_HIDDEN => 'Hidden',
    ];

    protected $fillable = [
        'profile_id',
        'privacy_level',           // legacy — global level (fallback if per-type not set)
        'show_profile_photo',      // legacy — boolean toggle
        'show_album_photos',       // legacy — boolean toggle
        'show_family_photos',      // legacy — boolean toggle
        'profile_photo_privacy',   // per-type privacy level (new)
        'album_photos_privacy',    // per-type privacy level (new)
        'family_photos_privacy',   // per-type privacy level (new)
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

    /**
     * Get the effective privacy level for a specific photo type.
     * Falls back to the legacy `privacy_level` if the per-type column isn't set.
     */
    public function levelForType(string $photoType): string
    {
        $key = match ($photoType) {
            'profile' => 'profile_photo_privacy',
            'album' => 'album_photos_privacy',
            'family' => 'family_photos_privacy',
            default => null,
        };

        if ($key && !empty($this->{$key})) {
            return $this->{$key};
        }

        // Fallback to legacy global level
        return $this->privacy_level ?: self::LEVEL_VISIBLE_TO_ALL;
    }

    /**
     * Is a photo of the given type visible to a viewer with the given relationship?
     *
     * @param  string  $photoType       'profile' | 'album' | 'family'
     * @param  string  $viewerRelation  'self' | 'interest_accepted' | 'member' | 'guest'
     * @return bool
     */
    public function isVisibleTo(string $photoType, string $viewerRelation): bool
    {
        // Owner always sees their own photos
        if ($viewerRelation === 'self') {
            return true;
        }

        $level = $this->levelForType($photoType);

        return match ($level) {
            self::LEVEL_VISIBLE_TO_ALL => true,
            self::LEVEL_INTEREST_ACCEPTED => $viewerRelation === 'interest_accepted',
            self::LEVEL_HIDDEN => false,
            default => true,
        };
    }
}
