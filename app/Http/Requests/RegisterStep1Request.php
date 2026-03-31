<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|min:2|max:100',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date|before:-18 years',
            'phone' => 'required|digits:10|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|max:14|confirmed',
            // Physical
            'height_cm' => 'nullable|integer|min:122|max:213',
            'complexion' => 'nullable|string',
            'body_type' => 'nullable|string',
            'physical_status' => 'nullable|string',
            'marital_status' => 'required|string',
            'children_with_me' => 'nullable|integer|min:0',
            'children_not_with_me' => 'nullable|integer|min:0',
            // Religion
            'religion' => 'required|string',
            // Christian conditional
            'denomination' => 'nullable|required_if:religion,Christian|string',
            'diocese' => 'nullable|string',
            'diocese_name' => 'nullable|string',
            'parish_name_place' => 'nullable|string',
            // Hindu/Jain conditional
            'caste' => 'nullable|string',
            'sub_caste' => 'nullable|string',
            'time_of_birth' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'rashi' => 'nullable|string',
            'nakshatra' => 'nullable|string',
            'gotra' => 'nullable|string',
            'dosh' => 'nullable|string',
            // Muslim conditional
            'muslim_sect' => 'nullable|string',
            'muslim_community' => 'nullable|string',
            'religious_observance' => 'nullable|string',
            // Jain
            'jain_sect' => 'nullable|string',
            // Other
            'other_religion_name' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'You must be at least 18 years old to register.',
            'phone.unique' => 'This phone number is already registered.',
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'Password and confirmation do not match.',
            'denomination.required_if' => 'Denomination is required for Christian profiles.',
        ];
    }
}
