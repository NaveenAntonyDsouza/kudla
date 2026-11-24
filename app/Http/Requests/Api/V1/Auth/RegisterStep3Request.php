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
     * Reference lists are at GET /api/v1/reference/{slug}. The working place
     * (working_country etc.) is sent in step 4.
     */
    public function bodyParameters(): array
    {
        return [
            'highest_education' => ['description' => 'Highest qualification: one item of the grouped list GET /reference/education-levels (send the item, not the group name). Max 100.', 'example' => 'B.Sc'],
            'education_level' => ['description' => 'Education level band from GET /reference/education-bands. Max 50.', 'example' => "Bachelor's"],
            'education_detail' => ['description' => 'Free-text specialisation or degree detail. Max 200.', 'required' => false, 'example' => 'Nursing'],
            'college_name' => ['description' => 'College / university name. Max 200.', 'required' => false],
            'occupation' => ['description' => 'Occupation: one item of the grouped list GET /reference/occupations. Max 100.', 'example' => 'Nurse'],
            'occupation_detail' => ['description' => 'Free-text occupation detail. Max 200.', 'required' => false],
            'employment_category' => ['description' => 'Employment category from GET /reference/employment-categories. Max 100.', 'example' => 'Private'],
            'employer_name' => ['description' => 'Current employer name. Max 200.', 'required' => false],
            'annual_income' => ['description' => 'Annual income band from GET /reference/income-ranges. Max 50.', 'example' => 'Upto 1 Lakh'],
        ];
    }
}
