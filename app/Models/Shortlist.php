<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shortlist extends Model
{
    protected $fillable = [
        'profile_id',
        'shortlisted_profile_id',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function shortlistedProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'shortlisted_profile_id');
    }
}
