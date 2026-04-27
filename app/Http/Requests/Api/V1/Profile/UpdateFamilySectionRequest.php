<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/family.
 *
 * Mirrors App\Http\Controllers\ProfileController::updateFamily.
 */
class UpdateFamilySectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'father_name' => 'nullable|string|max:100',
            'father_house_name' => 'nullable|string|max:100',
            'father_native_place' => 'nullable|string|max:100',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_house_name' => 'nullable|string|max:100',
            'mother_native_place' => 'nullable|string|max:100',
            'mother_occupation' => 'nullable|string|max:100',
            'family_status' => 'nullable|string|max:50',
            'brothers_married' => 'nullable|integer|min:0',
            'brothers_unmarried' => 'nullable|integer|min:0',
            'brothers_priest' => 'nullable|integer|min:0',
            'sisters_married' => 'nullable|integer|min:0',
            'sisters_unmarried' => 'nullable|integer|min:0',
            'sisters_nun' => 'nullable|integer|min:0',
            'candidate_asset_details' => 'nullable|string|max:500',
            'about_candidate_family' => 'nullable|string|max:5000',
        ];
    }
}
