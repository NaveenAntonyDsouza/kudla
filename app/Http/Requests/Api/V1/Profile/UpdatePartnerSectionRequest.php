<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/partner.
 *
 * Mirrors App\Http\Controllers\ProfileController::updatePartner with
 * two intentional corrections:
 *
 *   1. height_from_cm / height_to_cm are integers (the DB column is
 *      integer; web sends a string, which silently truncates). Using
 *      the same field name as the GET response removes ambiguity.
 *   2. Added `gte:height_from_cm` cross-field check alongside the
 *      existing `age_to gte:age_from`.
 *
 * The section dispatcher strips 'Any' from arrays and nulls empty
 * arrays, matching the web behaviour.
 */
class UpdatePartnerSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'age_from' => 'nullable|integer|min:18|max:70',
            'age_to' => 'nullable|integer|min:18|max:70|gte:age_from',
            'height_from_cm' => 'nullable|integer|min:100|max:250',
            'height_to_cm' => 'nullable|integer|min:100|max:250|gte:height_from_cm',
            'complexion' => 'nullable|array',
            'complexion.*' => 'string|max:50',
            'body_type' => 'nullable|array',
            'body_type.*' => 'string|max:50',
            'marital_status' => 'nullable|array',
            'marital_status.*' => 'string|max:50',
            'physical_status' => 'nullable|array',
            'physical_status.*' => 'string|max:50',
            'family_status' => 'nullable|array',
            'family_status.*' => 'string|max:50',
            'religions' => 'nullable|array',
            'religions.*' => 'string|max:50',
            'denomination' => 'nullable|array',
            'denomination.*' => 'string|max:50',
            'diocese' => 'nullable|array',
            'diocese.*' => 'string|max:100',
            'caste' => 'nullable|array',
            'caste.*' => 'string|max:50',
            'sub_caste' => 'nullable|array',
            'sub_caste.*' => 'string|max:50',
            'mother_tongues' => 'nullable|array',
            'mother_tongues.*' => 'string|max:50',
            'education_levels' => 'nullable|array',
            'education_levels.*' => 'string|max:100',
            'occupations' => 'nullable|array',
            'occupations.*' => 'string|max:100',
            'working_countries' => 'nullable|array',
            'working_countries.*' => 'string|max:100',
            'native_countries' => 'nullable|array',
            'native_countries.*' => 'string|max:100',
            'about_partner' => 'nullable|string|max:5000',
        ];
    }
}
