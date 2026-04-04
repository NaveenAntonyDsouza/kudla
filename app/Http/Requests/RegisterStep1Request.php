<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        // If user is already authenticated (came back from step 2),
        // ignore their own phone/email in unique check and make password optional
        if ($userId) {
            return [
                'full_name' => 'required|string|min:2|max:100',
                'gender' => 'required|in:male,female',
                'date_of_birth' => 'required|date|before:-18 years',
                'phone' => 'required|digits:10|unique:users,phone,' . $userId,
                'email' => 'required|email|unique:users,email,' . $userId,
                'password' => ['nullable', 'min:6', 'max:14', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
            ];
        }

        return [
            'full_name' => 'required|string|min:2|max:100',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date|before:-18 years',
            'phone' => 'required|digits:10|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'min:6', 'max:14', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'You must be at least 18 years old to register.',
            'phone.unique' => 'This phone number is already registered.',
            'email.unique' => 'This email is already registered.',
            'password.regex' => 'Must contain at least 1 uppercase, 1 lowercase, and 1 number.',
        ];
    }
}
