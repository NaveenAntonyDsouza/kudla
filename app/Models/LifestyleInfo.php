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
        'cultural_background',
        'favorite_music',
        'preferred_books',
        'preferred_movies',
        'sports_fitness_games',
        'favorite_cuisine',
    ];

    protected function casts(): array
    {
        return [
            'hobbies' => 'array',
            'interests' => 'array',
            'languages_known' => 'array',
            'favorite_music' => 'array',
            'preferred_books' => 'array',
            'preferred_movies' => 'array',
            'sports_fitness_games' => 'array',
            'favorite_cuisine' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
