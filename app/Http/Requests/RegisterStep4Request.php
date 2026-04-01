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
            'native_state' => 'nullable|string|max:100',
            'native_district' => 'nullable|string|max:100',
            // Contact
            'whatsapp_number' => 'nullable|string|max:15',
            'mobile_number' => 'required|string|max:15',
            'custodian_name' => 'required|string|max:100',
            'custodian_relation' => 'required|string|max:100',
            // Address
            'communication_address' => 'required|string|max:200',
            'pin_zip_code' => 'required|string|max:10',
        ];
    }
}
