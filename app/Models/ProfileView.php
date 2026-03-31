<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'viewer_profile_id',
        'viewed_profile_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function viewerProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'viewer_profile_id');
    }

    public function viewedProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'viewed_profile_id');
    }
}
