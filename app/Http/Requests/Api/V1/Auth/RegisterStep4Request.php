<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\RegisterStep4Request as WebRegisterStep4Request;

/**
 * API validation for POST /api/v1/auth/register/step-4. Inherits web rules.
 */
class RegisterStep4Request extends WebRegisterStep4Request
{
    /**
     * Scribe body-parameter metadata for the native-place / contact step.
     * native_state + native_district become required when native_country
     * is "India" — see rules() for the conditional logic.
     */
    public function bodyParameters(): array
    {
        return [
            'native_country' => ['description' => 'Native country.', 'example' => 'India'],
            'native_state' => ['description' => 'Native state. Required when native_country="India".', 'required' => false, 'example' => 'Karnataka'],
            'native_district' => ['description' => 'Native district. Required when native_country="India".', 'required' => false, 'example' => 'Dakshina Kannada'],
            'whatsapp_number' => ['description' => 'WhatsApp number for follow-up. Up to 15 chars (international tolerant).', 'required' => false],
            'mobile_number' => ['description' => 'Primary mobile number for the profile. Up to 15 chars.', 'example' => '9876543210'],
            'custodian_name' => ['description' => 'Profile custodian (parent/sibling/relative) handling enquiries.', 'required' => false],
            'custodian_relation' => ['description' => 'Relationship of custodian to the candidate (e.g. Father, Mother, Brother).', 'required' => false],
            'communication_address' => ['description' => 'Postal address for communication. Up to 200 chars.', 'example' => '123 Main Street, Mangalore'],
            'pin_zip_code' => ['description' => 'PIN / ZIP code. Up to 10 chars.', 'example' => '575001'],
        ];
    }
}
