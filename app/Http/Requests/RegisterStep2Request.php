<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Physical. complexion / body_type / physical_status (+DA) and
            // family_status moved to onboarding step 1 to slim registration.
            'height' => 'required|string',
            'marital_status' => 'required|string',
            'children_with_me' => 'nullable|integer|min:0',
            'children_not_with_me' => 'nullable|integer|min:0',
            // Languages — mother tongue is a core match field (required);
            // other languages known is an optional multi-select.
            'mother_tongue' => 'required|string|max:50',
            'languages_known' => 'nullable|array',
            'languages_known.*' => 'string|max:50',
            // Religion
            'religion' => 'required|string',
            // Christian conditional
            'denomination' => 'nullable|required_if:religion,Christian|string',
            'other_denomination_name' => 'nullable|required_if:denomination,Other|string|max:100',
            'diocese' => 'nullable|string',
            'diocese_name' => 'nullable|string',
            'parish_name_place' => 'nullable|string',
            // Hindu/Jain conditional. Horoscope cluster (time/place of birth,
            // rashi, nakshatra, gotra, manglik, jathakam) moved to onboarding.
            'caste' => 'nullable|required_if:religion,Hindu|required_if:religion,Jain|string',
            'other_caste_name' => 'nullable|string|max:100',
            'sub_caste' => 'nullable|string',
            // Muslim conditional
            'muslim_sect' => 'nullable|required_if:religion,Muslim|string',
            'muslim_community' => 'nullable|string',
            'religious_observance' => 'nullable|string',
            // Jain
            'jain_sect' => 'nullable|string',
            // Other
            'other_religion_name' => 'nullable|required_if:religion,Other|string',
        ];
    }

    public function messages(): array
    {
        return [
            'mother_tongue.required' => 'Please select your mother tongue.',
            'denomination.required_if' => 'Denomination is required for Christian profiles.',
            'other_denomination_name.required_if' => 'Please specify the denomination.',
            'caste.required_if' => 'Caste/Community is required for Hindu and Jain profiles.',
            'muslim_sect.required_if' => 'Sect is required for Muslim profiles.',
            'other_religion_name.required_if' => 'Please specify your religion.',
        ];
    }
}
