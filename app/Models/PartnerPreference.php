<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPreference extends Model
{
    protected $fillable = [
        'profile_id',
        // Primary
        'age_from',
        'age_to',
        'height_from_cm',
        'height_to_cm',
        'marital_status',
        'complexion',
        'body_type',
        'children_status',
        'physical_status',
        'da_category',
        'family_status',
        // Religious
        'religions',
        'denomination',
        'diocese',
        'caste',
        'sub_caste',
        'muslim_sect',
        'muslim_community',
        'jain_sect',
        'manglik',
        'mother_tongues',
        'languages_known',
        // Professional
        'education_levels',
        'educational_qualifications',
        'occupations',
        'employment_status',
        'income_range',
        'income_from',
        'income_to',
        // Location
        'countries',
        'states',
        'cities',
        'working_countries',
        'working_states',
        'working_districts',
        'native_countries',
        'native_states',
        'native_districts',
        // Other
        'diet',
        'smoking',
        'drinking',
        'communities',
        'about_partner',
    ];

    protected function casts(): array
    {
        return [
            'age_from' => 'integer',
            'age_to' => 'integer',
            'height_from_cm' => 'string',
            'height_to_cm' => 'string',
            'marital_status' => 'array',
            'complexion' => 'array',
            'body_type' => 'array',
            'children_status' => 'array',
            'family_status' => 'array',
            'religions' => 'array',
            'denomination' => 'array',
            'diocese' => 'array',
            'caste' => 'array',
            'sub_caste' => 'array',
            'muslim_sect' => 'array',
            'muslim_community' => 'array',
            'jain_sect' => 'array',
            'manglik' => 'array',
            'mother_tongues' => 'array',
            'languages_known' => 'array',
            'education_levels' => 'array',
            'educational_qualifications' => 'array',
            'occupations' => 'array',
            'physical_status' => 'array',
            'da_category' => 'array',
            'employment_status' => 'array',
            'income_range' => 'array',
            'communities' => 'array',
            'countries' => 'array',
            'states' => 'array',
            'cities' => 'array',
            'working_countries' => 'array',
            'working_states' => 'array',
            'working_districts' => 'array',
            'native_countries' => 'array',
            'native_states' => 'array',
            'native_districts' => 'array',
            'diet' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
