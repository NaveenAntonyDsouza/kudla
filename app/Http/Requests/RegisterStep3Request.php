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
            'father_name' => 'nullable|string|max:100',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_occupation' => 'nullable|string|max:100',
            'family_type' => 'nullable|string|max:50',
            'family_values' => 'nullable|string|max:50',
            'family_status' => 'nullable|string|max:50',
            'num_brothers' => 'nullable|integer|min:0|max:20',
            'brothers_married' => 'nullable|integer|min:0|max:20',
            'brothers_unmarried' => 'nullable|integer|min:0|max:20',
            'brothers_priest' => 'nullable|integer|min:0|max:20',
            'num_sisters' => 'nullable|integer|min:0|max:20',
            'sisters_married' => 'nullable|integer|min:0|max:20',
            'sisters_unmarried' => 'nullable|integer|min:0|max:20',
            'sisters_nun' => 'nullable|integer|min:0|max:20',
            'family_living_in' => 'nullable|string|max:100',
            'father_house_name' => 'nullable|string|max:100',
            'father_native_place' => 'nullable|string|max:100',
            'mother_house_name' => 'nullable|string|max:100',
            'mother_native_place' => 'nullable|string|max:100',
            'candidate_asset_details' => 'nullable|string|max:500',
            'about_family' => 'nullable|string|max:1000',
            'about_candidate_family' => 'nullable|string|max:1000',
        ];
    }
}
