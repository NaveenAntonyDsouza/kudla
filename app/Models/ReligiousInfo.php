<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReligiousInfo extends Model
{
    protected $table = 'religious_info';

    protected $fillable = [
        'profile_id',
        'religion',
        'caste',
        'sub_caste',
        'gotra',
        'nakshatra',
        'rashi',
        'dosh',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
