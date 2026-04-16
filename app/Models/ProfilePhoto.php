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
        'approval_status',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_visible' => 'boolean',
            'display_order' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    // Approval status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Rejection reasons
    const REJECTION_REASONS = [
        'blurry' => 'Blurry / Low quality',
        'not_real' => 'Not a real photo of the person',
        'inappropriate' => 'Inappropriate / Objectionable content',
        'group_photo' => 'Group photo (single person required)',
        'celebrity' => 'Celebrity / Fake photo',
        'text_watermark' => 'Contains text or watermark',
        'duplicate' => 'Duplicate photo',
        'other' => 'Other',
    ];

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

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', self::STATUS_PENDING);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('approval_status', self::STATUS_REJECTED);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('photo_type', $type);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Visible AND approved — for public-facing queries (other users viewing the profile).
     */
    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->where('approval_status', self::STATUS_APPROVED);
    }

    // Relationships

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helpers

    public function isPending(): bool
    {
        return $this->approval_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::STATUS_REJECTED;
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
