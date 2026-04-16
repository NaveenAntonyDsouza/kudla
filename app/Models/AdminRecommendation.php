<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRecommendation extends Model
{
    protected $fillable = [
        'for_profile_id',
        'recommended_profile_id',
        'admin_user_id',
        'admin_note',
        'priority',
        'is_viewed',
        'interest_sent',
    ];

    protected function casts(): array
    {
        return [
            'is_viewed' => 'boolean',
            'interest_sent' => 'boolean',
        ];
    }

    public function forProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'for_profile_id');
    }

    public function recommendedProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'recommended_profile_id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
