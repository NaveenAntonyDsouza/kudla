<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;

/**
 * Validation for POST /api/v1/auth/register/step-1.
 *
 * Unlike the web version (App\Http\Requests\RegisterStep1Request), the API
 * version does NOT have the "already authenticated user re-editing step 1"
 * branch. The mobile flow is always:
 *
 *   client POSTs step-1 with no token  ->  server creates User+Profile
 *                                       ->  server returns Sanctum token
 *                                       ->  client uses token for steps 2-5
 *
 * So password is always required and phone/email must be globally unique.
 */
class RegisterStep1Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => 'required|string|min:2|max:100',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date|before:-18 years',
            'phone' => 'required|digits:10|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|max:14',
            'ref' => 'nullable|string|max:20',  // optional affiliate code
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'You must be at least 18 years old to register.',
            'phone.unique' => 'This phone number is already registered. Try logging in instead.',
            'email.unique' => 'This email is already registered. Try logging in instead.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.max' => 'Password must be no more than 14 characters.',
            'gender.in' => 'Gender must be either "male" or "female".',
        ];
    }
}
