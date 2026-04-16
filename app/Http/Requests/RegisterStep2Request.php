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
            // Physical
            'height' => 'required|string',
            'complexion' => 'required|string',
            'body_type' => 'required|string',
            'physical_status' => 'required|string',
            'da_category' => 'nullable|required_if:physical_status,Differently Abled|string',
            'da_category_other' => 'nullable|required_if:da_category,Other|string|max:50',
            'da_description' => 'nullable|required_if:physical_status,Differently Abled|string|max:500',
            'marital_status' => 'required|string',
            'children_with_me' => 'nullable|integer|min:0',
            'children_not_with_me' => 'nullable|integer|min:0',
            'family_status' => 'required|string',
            // Religion
            'religion' => 'required|string',
            // Christian conditional
            'denomination' => 'nullable|required_if:religion,Christian|string',
            'diocese' => 'nullable|string',
            'diocese_name' => 'nullable|string',
            'parish_name_place' => 'nullable|string',
            // Hindu/Jain conditional
            'caste' => 'nullable|required_if:religion,Hindu|required_if:religion,Jain|string',
            'sub_caste' => 'nullable|string',
            'time_of_birth' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'rashi' => 'nullable|string',
            'nakshatra' => 'nullable|string',
            'gotra' => 'nullable|string',
            'manglik' => 'nullable|string',
            'jathakam' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
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
            'denomination.required_if' => 'Denomination is required for Christian profiles.',
            'caste.required_if' => 'Caste/Community is required for Hindu and Jain profiles.',
            'muslim_sect.required_if' => 'Sect is required for Muslim profiles.',
            'da_category.required_if' => 'Please select a category for Differently Abled.',
            'da_category_other.required_if' => 'Please specify the category.',
            'da_description.required_if' => 'Please describe the condition.',
            'other_religion_name.required_if' => 'Please specify your religion.',
            'jathakam.max' => 'Horoscope file must be less than 2MB.',
            'jathakam.mimes' => 'Horoscope must be JPG, PNG or PDF.',
        ];
    }
}
