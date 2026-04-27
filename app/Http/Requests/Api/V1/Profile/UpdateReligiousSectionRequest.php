<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/religious.
 *
 * Mirrors App\Http\Controllers\ProfileController::updateReligious,
 * minus the `jathakam` file upload — API is JSON-only at step-6.
 * File uploads will live at a dedicated endpoint (similar to photo
 * upload) added in a later step.
 *
 * Translation: the `manglik` API field writes to the `dosh` DB column
 * (column naming follows the web history; API exposes the user-friendly
 * name). The section dispatcher handles the rename.
 */
class UpdateReligiousSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'religion' => 'required|string|max:50',
            'caste' => 'nullable|required_if:religion,Hindu|required_if:religion,Jain|string|max:50',
            'sub_caste' => 'nullable|string|max:50',
            'gotra' => 'nullable|string|max:50',
            'nakshatra' => 'nullable|string|max:50',
            'rashi' => 'nullable|string|max:50',
            'manglik' => 'nullable|string|max:20',
            'denomination' => 'nullable|required_if:religion,Christian|string|max:50',
            'diocese' => 'nullable|string|max:100',
            'diocese_name' => 'nullable|string|max:100',
            'parish_name_place' => 'nullable|string|max:200',
            'time_of_birth' => 'nullable|string|max:20',
            'place_of_birth' => 'nullable|string|max:100',
            'muslim_sect' => 'nullable|required_if:religion,Muslim|string|max:50',
            'muslim_community' => 'nullable|string|max:50',
            'religious_observance' => 'nullable|string|max:50',
            'jain_sect' => 'nullable|string|max:50',
            'other_religion_name' => 'nullable|required_if:religion,Other|string|max:50',
        ];
    }
}
