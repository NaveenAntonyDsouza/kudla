<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\RegisterStep3Request as WebRegisterStep3Request;

/**
 * API validation for POST /api/v1/auth/register/step-3. Inherits web rules.
 */
class RegisterStep3Request extends WebRegisterStep3Request
{
    /**
     * Scribe body-parameter metadata for the education / occupation step.
     * Reference lists are at GET /api/v1/reference/{slug}.
     */
    public function bodyParameters(): array
    {
        return [
            'highest_education' => ['description' => 'Highest qualification (degree name).', 'example' => 'Bachelor of Engineering'],
            'education_level' => ['description' => 'Education level bucket. Reference list at GET /reference/education-levels.', 'example' => 'Bachelors'],
            'education_detail' => ['description' => 'Free-text specialisation or degree detail.', 'required' => false, 'example' => 'Computer Science'],
            'college_name' => ['description' => 'College / university name.', 'required' => false],
            'occupation' => ['description' => 'Occupation. Reference list at GET /reference/occupations.', 'example' => 'Software Engineer'],
            'occupation_detail' => ['description' => 'Free-text occupation detail.', 'required' => false],
            'employment_category' => ['description' => 'Employment category (Government/Private/Self-Employed/etc).', 'example' => 'Private Sector'],
            'employer_name' => ['description' => 'Current employer name.', 'required' => false],
            'annual_income' => ['description' => 'Annual income bucket. Reference list at GET /reference/income-ranges.', 'example' => '5-10 LPA'],
            'working_country' => ['description' => 'Country where the user works currently.', 'example' => 'India'],
            'working_state' => ['description' => 'Working state (free-text).', 'required' => false],
            'working_district' => ['description' => 'Working district (free-text).', 'required' => false],
        ];
    }
}
