<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep5Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'created_by' => 'required|string|max:50',
            'creator_name' => 'required|string|max:100',
            'creator_contact_number' => 'required|string|max:15',
            'how_did_you_hear_about_us' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'creator_name.required' => 'Creator name is required.',
            'creator_contact_number.required' => 'Creator contact number is required.',
            'how_did_you_hear_about_us.required' => 'Please tell us how you heard about us.',
        ];
    }
}
