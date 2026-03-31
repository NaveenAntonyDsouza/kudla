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
            // Location
            'country' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'native_place' => 'nullable|string|max:100',
            'native_country' => 'nullable|string|max:100',
            'native_state' => 'nullable|string|max:100',
            'native_district' => 'nullable|string|max:100',
            'pin_zip_code' => 'nullable|string|max:10',
            'citizenship' => 'nullable|string|max:100',
            'residency_status' => 'nullable|string|max:50',
            'grew_up_in' => 'nullable|string|max:100',
            // Contact
            'contact_person' => 'nullable|string|max:100',
            'contact_relationship' => 'nullable|string|max:50',
            'primary_phone' => 'nullable|string|max:15',
            'secondary_phone' => 'nullable|string|max:15',
            'whatsapp_number' => 'nullable|string|max:15',
            'communication_address' => 'nullable|string|max:500',
            'present_address' => 'nullable|string|max:500',
            'present_pin_zip_code' => 'nullable|string|max:10',
            'permanent_address' => 'nullable|string|max:500',
            'permanent_pin_zip_code' => 'nullable|string|max:10',
        ];
    }
}
