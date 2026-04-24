<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/education.
 *
 * Mirrors App\Http\Controllers\ProfileController::updateEducation.
 * All fields nullable — users can save partial education info as they
 * gather the exact details (college name, annual income, etc.).
 */
class UpdateEducationSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'highest_education' => 'nullable|string|max:100',
            'education_detail' => 'nullable|string|max:200',
            'college_name' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:100',
            'occupation_detail' => 'nullable|string|max:200',
            'employer_name' => 'nullable|string|max:100',
            'annual_income' => 'nullable|string|max:50',
            'working_country' => 'nullable|string|max:100',
            'working_state' => 'nullable|string|max:100',
            'working_district' => 'nullable|string|max:100',
        ];
    }
}
