<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/primary.
 *
 * "Primary" columns live on the profiles table itself with one exception:
 * `languages_known` is stored on lifestyle_info (per existing web
 * controller convention). The section dispatcher handles the split.
 *
 * Rules mirror App\Http\Controllers\ProfileController::updatePrimary
 * verbatim so web and API stay in lockstep. Enhancements belong in a
 * separate retrofit pass so web+API diverge intentionally, not by drift.
 */
class UpdatePrimarySectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'weight_kg' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'mother_tongue' => 'required|string|max:50',
            'languages_known' => 'nullable|array',
            'languages_known.*' => 'string|max:50',
            'complexion' => 'nullable|string|max:30',
            'body_type' => 'nullable|string|max:30',
            'about_me' => 'nullable|string|max:5000',
        ];
    }
}
