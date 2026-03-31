<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationDetail extends Model
{
    protected $fillable = [
        'profile_id',
        'highest_education',
        'education_detail',
        'college_name',
        'occupation',
        'occupation_detail',
        'employer_name',
        'annual_income',
        'working_city',
        'education_level',
        'employment_category',
        'working_country',
        'working_state',
        'working_district',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
