<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep4Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Native place
            'native_country' => 'required|string|max:100',
            'native_state' => 'nullable|required_if:native_country,India|string|max:100',
            'native_district' => 'nullable|required_if:native_country,India|string|max:100',
            // Contact
            'whatsapp_number' => 'nullable|string|max:15',
            'mobile_number' => 'required|string|max:15',
            'custodian_name' => 'nullable|string|max:100',
            'custodian_relation' => 'nullable|string|max:100',
            // Address
            'communication_address' => 'required|string|max:200',
            'pin_zip_code' => 'required|string|max:10',
            // Profile creation details (merged from the former Step 5).
            // When the user picks "Self / Candidate" the view fills creator_name +
            // creator_contact_number with their own user data via hidden inputs,
            // so these stay required even in the self-creator case.
            'created_by' => 'required|string|max:50',
            'creator_name' => 'required|string|max:100',
            'creator_contact_number' => 'required|string|max:15',
            'how_did_you_hear_about_us' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'native_state.required_if' => 'State is required for India.',
            'native_district.required_if' => 'District is required for India.',
            'creator_name.required' => 'Creator name is required.',
            'creator_contact_number.required' => 'Creator contact number is required.',
        ];
    }
}
