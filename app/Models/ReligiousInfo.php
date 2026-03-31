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
        'denomination',
        'diocese',
        'diocese_name',
        'parish_name_place',
        'time_of_birth',
        'place_of_birth',
        'jathakam_upload_url',
        'muslim_sect',
        'muslim_community',
        'religious_observance',
        'jain_sect',
        'other_religion_name',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
