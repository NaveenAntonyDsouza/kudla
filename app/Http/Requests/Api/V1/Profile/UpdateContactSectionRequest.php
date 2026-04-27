<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validates PUT /api/v1/profile/me/contact.
 *
 * Mirrors App\Http\Controllers\ProfileController::updateContact.
 *
 * Note: `phone` and `email` are NOT accepted here even though the
 * ProfileResource contact section surfaces them. They live on the User
 * model and changing them requires OTP re-verification (future
 * /account/phone + /account/email endpoints). If a Flutter client sends
 * them round-tripped from the GET response, Laravel's validated() drops
 * them silently — matches web behaviour.
 */
class UpdateContactSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'whatsapp_number' => 'nullable|string|max:15',
            'secondary_phone' => 'nullable|string|max:15',
            'residential_phone_number' => 'nullable|string|max:20',
            'preferred_call_time' => 'nullable|string|max:30',
            'alternate_email' => 'nullable|email|max:150',
            'reference_name' => 'nullable|string|max:100',
            'reference_relationship' => 'nullable|string|max:50',
            'reference_mobile' => 'nullable|string|max:15',
            'communication_address' => 'nullable|string|max:200',
            'present_address' => 'nullable|string|max:200',
            'present_pin_zip_code' => 'nullable|string|max:10',
            'permanent_address' => 'nullable|string|max:200',
            'permanent_pin_zip_code' => 'nullable|string|max:10',
        ];
    }
}
