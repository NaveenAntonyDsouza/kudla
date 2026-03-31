<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoRequest extends Model
{
    protected $fillable = [
        'requester_profile_id',
        'target_profile_id',
        'status',
    ];

    public function requesterProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'requester_profile_id');
    }

    public function targetProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'target_profile_id');
    }
}
