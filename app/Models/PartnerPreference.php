<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPreference extends Model
{
    protected $fillable = [
        'profile_id',
        'age_from',
        'age_to',
        'height_from_cm',
        'height_to_cm',
        'marital_status',
        'religions',
        'communities',
        'education_levels',
        'occupations',
        'countries',
        'states',
        'cities',
        'income_from',
        'income_to',
        'diet',
        'smoking',
        'drinking',
        'physical_status',
        'mother_tongues',
        'about_partner',
    ];

    protected function casts(): array
    {
        return [
            'age_from' => 'integer',
            'age_to' => 'integer',
            'height_from_cm' => 'integer',
            'height_to_cm' => 'integer',
            'marital_status' => 'array',
            'religions' => 'array',
            'communities' => 'array',
            'education_levels' => 'array',
            'occupations' => 'array',
            'countries' => 'array',
            'states' => 'array',
            'cities' => 'array',
            'diet' => 'array',
            'mother_tongues' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
