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
     * is "India". created_by / creator_name / creator_contact_number are required here AND
     * again in step 5 (the website merged step 5 into step 4; the app keeps 5 calls).
     */
    public function bodyParameters(): array
    {
        return [
            'native_country' => ['description' => 'Native country. Reference list at GET /reference/countries.', 'example' => 'United Kingdom'],
            'native_state' => ['description' => 'Native state. Required when native_country="India".', 'required' => false, 'example' => 'Karnataka'],
            'native_district' => ['description' => 'Native district. Required when native_country="India".', 'required' => false, 'example' => 'Dakshina Kannada'],
            'native_place' => ['description' => 'Native town or village (free text). Max 100.', 'required' => false],
            'working_country' => ['description' => 'Country where the member lives or works now: the main location used in matches. Max 100.', 'example' => 'United Kingdom'],
            'working_state' => ['description' => 'Working state / region (free text). Max 100.', 'required' => false],
            'working_district' => ['description' => 'Working district (free text). Max 100.', 'required' => false],
            'working_city' => ['description' => 'Working city (free text). Max 100.', 'required' => false, 'example' => 'Leeds'],
            'mobile_number' => ['description' => 'Primary mobile number for the profile. Up to 15 characters, e.g. "+447700900123".', 'example' => '+447700900123'],
            'whatsapp_number' => ['description' => 'WhatsApp number. Up to 15 characters.', 'required' => false],
            'created_by' => ['description' => 'Who is creating this profile, from GET /reference/created-by. On a dating app send "Self / Candidate".', 'example' => 'Self / Candidate'],
            'creator_name' => ['description' => 'Name of the person creating the profile (for "Self / Candidate": their own name). Max 100.', 'example' => 'Maria Fernandes'],
            'creator_contact_number' => ['description' => 'Contact number of that person (for "Self / Candidate": their own number). Up to 15 characters.', 'example' => '+447700900123'],
            'how_did_you_hear_about_us' => ['description' => 'How the member heard about us. Optional. Max 100.', 'required' => false],
            'custodian_name' => ['description' => 'Optional; normally sent in onboarding step 2.', 'required' => false],
            'custodian_relation' => ['description' => 'Optional; normally sent in onboarding step 2.', 'required' => false],
            'communication_address' => ['description' => 'Optional; normally sent in onboarding step 2.', 'required' => false],
            'pin_zip_code' => ['description' => 'Optional; normally sent in onboarding step 2. Up to 10 characters.', 'required' => false],
        ];
    }
}
