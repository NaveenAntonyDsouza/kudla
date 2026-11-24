<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\RegisterStep5Request as WebRegisterStep5Request;

/**
 * API validation for POST /api/v1/auth/register/step-5. Inherits web rules.
 */
class RegisterStep5Request extends WebRegisterStep5Request
{
    /**
     * Scribe body-parameter metadata for the final step — registers who
     * is creating the profile (self vs. parent/sibling) and acquisition
     * channel for analytics.
     */
    public function bodyParameters(): array
    {
        return [
            'created_by' => ['description' => 'Who is creating this profile, from GET /reference/created-by (the same as step 4). On a dating app send "Self / Candidate".', 'example' => 'Self / Candidate'],
            'creator_name' => ['description' => 'Name of the person creating the profile.', 'example' => 'Naveen DSouza'],
            'creator_contact_number' => ['description' => 'Contact number of the creator. Up to 15 chars.', 'example' => '+447700900123'],
            'how_did_you_hear_about_us' => ['description' => 'Acquisition channel — used for marketing analytics.', 'required' => false, 'example' => 'Friend referral'],
        ];
    }
}
