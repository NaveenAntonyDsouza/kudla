<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/social.
 *
 * Mirrors App\Http\Controllers\ProfileController::updateSocial.
 * Each URL is optional; provide the handful a user wants to share,
 * omit the rest. `url` rule accepts http/https schemes only.
 */
class UpdateSocialSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'facebook_url' => 'nullable|url|max:200',
            'instagram_url' => 'nullable|url|max:200',
            'linkedin_url' => 'nullable|url|max:200',
            'youtube_url' => 'nullable|url|max:200',
            'website_url' => 'nullable|url|max:200',
        ];
    }
}
