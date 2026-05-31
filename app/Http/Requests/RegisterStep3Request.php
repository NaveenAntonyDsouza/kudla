<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'highest_education' => 'required|string|max:100',
            'education_level' => 'required|string|max:50',
            'education_detail' => 'nullable|string|max:200',
            'college_name' => 'nullable|string|max:200',
            'occupation' => 'required|string|max:100',
            'occupation_detail' => 'nullable|string|max:200',
            'employment_category' => 'required|string|max:100',
            'employer_name' => 'nullable|string|max:200',
            'annual_income' => 'required|string|max:50',
            // Working location (country/state/district/city) moved to step 4
            // so all geography lives on one step; step 3 is now purely
            // education + career.
        ];
    }
}
