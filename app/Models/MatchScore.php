<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Future-ready model for cached match scores.
 * Not used in v1 — scores are calculated on-the-fly in MatchingService.
 * Activate in v2 when user count exceeds 10K.
 */
class MatchScore extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'profile_id',
        'matched_profile_id',
        'score',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function matchedProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'matched_profile_id');
    }
}
