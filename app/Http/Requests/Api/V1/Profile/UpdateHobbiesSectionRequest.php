<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/hobbies.
 *
 * Mirrors App\Http\Controllers\ProfileController::updateHobbies.
 *
 * Arrays follow REPLACE semantics — sending `{hobbies: ["music"]}`
 * overwrites the existing list. Deselected (omitted) arrays are
 * explicitly nulled by the section dispatcher so the next GET returns
 * [] instead of the stale previous selection.
 *
 * The dispatcher also preserves `languages_known` from the existing row
 * because the "primary" section owns that field and hobbies mustn't
 * wipe it.
 */
class UpdateHobbiesSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'diet' => 'nullable|string|max:30',
            'drinking' => 'nullable|string|max:20',
            'smoking' => 'nullable|string|max:20',
            'cultural_background' => 'nullable|string|max:30',
            'hobbies' => 'nullable|array',
            'hobbies.*' => 'string|max:80',
            'favorite_music' => 'nullable|array',
            'favorite_music.*' => 'string|max:80',
            'preferred_books' => 'nullable|array',
            'preferred_books.*' => 'string|max:80',
            'preferred_movies' => 'nullable|array',
            'preferred_movies.*' => 'string|max:80',
            'sports_fitness_games' => 'nullable|array',
            'sports_fitness_games.*' => 'string|max:80',
            'favorite_cuisine' => 'nullable|array',
            'favorite_cuisine.*' => 'string|max:80',
        ];
    }
}
