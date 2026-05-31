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
            'native_place' => 'nullable|string|max:100',
            // Working location — moved here from step 3 so all geography is
            // grouped on one step. Working location is the primary location
            // used in partner-preference matching.
            'working_country' => 'required|string|max:100',
            'working_state' => 'nullable|string|max:100',
            'working_district' => 'nullable|string|max:100',
            'working_city' => 'nullable|string|max:100',
            // Contact. Custodian name/relation, communication_address and
            // pin_zip_code moved to onboarding step 2 to slim registration.
            'whatsapp_number' => 'nullable|string|max:15',
            'mobile_number' => 'required|string|max:15',
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
