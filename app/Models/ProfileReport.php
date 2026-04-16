<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileReport extends Model
{
    protected $fillable = [
        'reporter_profile_id',
        'reported_profile_id',
        'reason',
        'description',
        'status',
        'admin_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reporterProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'reporter_profile_id');
    }

    public function reportedProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'reported_profile_id');
    }

    public static function reasons(): array
    {
        return [
            'fake_profile' => 'Fake or misleading profile',
            'inappropriate_photo' => 'Inappropriate or fake photo',
            'harassment' => 'Harassment or abusive behaviour',
            'fraud' => 'Fraud or scam attempt',
            'already_married' => 'Already married',
            'wrong_info' => 'Wrong or false information',
            'other' => 'Other',
        ];
    }
}
