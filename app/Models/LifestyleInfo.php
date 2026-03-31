<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifestyleInfo extends Model
{
    protected $table = 'lifestyle_info';

    protected $fillable = [
        'profile_id',
        'diet',
        'smoking',
        'drinking',
        'hobbies',
        'interests',
        'languages_known',
    ];

    protected function casts(): array
    {
        return [
            'hobbies' => 'array',
            'interests' => 'array',
            'languages_known' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
