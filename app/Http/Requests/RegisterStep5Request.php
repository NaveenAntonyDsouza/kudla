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
            'creator_name' => 'nullable|string|max:100',
            'creator_contact_number' => 'nullable|string|max:15',
            'how_did_you_hear_about_us' => 'nullable|string|max:100',
        ];
    }
}
