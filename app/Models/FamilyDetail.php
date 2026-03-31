<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyDetail extends Model
{
    protected $fillable = [
        'profile_id',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'family_type',
        'family_values',
        'family_status',
        'num_brothers',
        'brothers_married',
        'num_sisters',
        'sisters_married',
        'family_living_in',
        'about_family',
    ];

    protected function casts(): array
    {
        return [
            'num_brothers' => 'integer',
            'brothers_married' => 'integer',
            'num_sisters' => 'integer',
            'sisters_married' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
