# Step 5 — Api V1 FormRequest Base Pattern

## Goal
Establish a base FormRequest class under `App\Http\Requests\Api\V1\` so all API validation errors produce envelope-shaped responses. Subclass existing web FormRequests where rules are identical.

## Prerequisites
- [ ] [step-04 — RegistrationService](step-04-extract-registration-service.md) complete

## Procedure

### 1. Create directory and base class

```bash
mkdir -p app/Http/Requests/Api/V1/Auth
```

Create `app/Http/Requests/Api/V1/ApiFormRequest.php`:

```php
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base class for all API v1 FormRequests.
 *
 * All API validation errors are caught by ApiExceptionHandler and
 * converted to envelope shape. This base exists for:
 * - Common API-specific hooks (future)
 * - Default authorize() = true (most API routes authorize via middleware/policies, not FormRequest)
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;  // Middleware handles access; override in subclass if per-field authz needed
    }
}
```

### 2. Create the step 1 FormRequest

Create `app/Http/Requests/Api/V1/Auth/RegisterStep1Request.php`:

```php
<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;

class RegisterStep1Request extends ApiFormRequest
{
    public function rules(): array
    {
        $minPwd = config('matrimony.password_min_length', 6);
        $maxPwd = config('matrimony.password_max_length', 14);
        $minAge = config('matrimony.registration_min_age', 18);
        $maxDob = now()->subYears($minAge)->format('Y-m-d');

        return [
            'full_name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:190|unique:users,email',
            'phone' => 'required|digits:10|unique:users,phone',
            'password' => "required|string|min:{$minPwd}|max:{$maxPwd}|confirmed",
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => "required|date|before_or_equal:{$maxDob}",
            'ref' => 'nullable|string|max:20',  // affiliate code
        ];
    }

    public function messages(): array
    {
        return [
            'phone.digits' => 'Phone must be exactly 10 digits.',
            'phone.unique' => 'This phone number is already registered. Try logging in.',
            'email.unique' => 'This email is already registered. Try logging in.',
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old to register.',
            'password.confirmed' => 'Passwords do not match.',
        ];
    }
}
```

### 3. Create remaining step FormRequests

For step 2-5, **subclass** existing web FormRequests (they have identical rules). Example for step 2:

```php
// app/Http/Requests/Api/V1/Auth/RegisterStep2Request.php
<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\RegisterStep2Request as WebRequest;

class RegisterStep2Request extends WebRequest
{
    public function authorize(): bool
    {
        return true;  // Override to API default
    }
}
```

Repeat for `RegisterStep3Request`, `RegisterStep4Request`, `RegisterStep5Request`.

### 4. Smoke-test via test

Create `tests/Feature/Api/V1/Auth/RegisterStep1ValidationTest.php`:

```php
<?php

use function Pest\Laravel\postJson;

it('rejects invalid payload with envelope 422', function () {
    $response = postJson('/api/v1/auth/register/step-1', []);

    $response->assertStatus(422)
        ->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']])
        ->assertJsonStructure(['error' => ['fields' => ['full_name', 'email', 'phone', 'password', 'gender', 'date_of_birth']]]);
});

it('rejects underage DOB', function () {
    $response = postJson('/api/v1/auth/register/step-1', [
        'full_name' => 'Test',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'Male',
        'date_of_birth' => now()->subYears(17)->format('Y-m-d'),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.fields.date_of_birth.0', 'You must be at least 18 years old to register.');
});
```

> **Note:** the route `/api/v1/auth/register/step-1` doesn't exist yet (next step). These tests will run green after step 6.

## Verification

- [ ] `App\Http\Requests\Api\V1\ApiFormRequest` exists as abstract
- [ ] Step 1 through 5 FormRequests exist under `Api\V1\Auth\`
- [ ] Steps 2-5 subclass the web FormRequests

## Commit

```bash
git add app/Http/Requests/Api/V1/ tests/Feature/Api/V1/Auth/RegisterStep1ValidationTest.php
git commit -m "phase-2a wk-02: step-05 API FormRequest base + register step 1-5 requests"
```

## Next step
→ [step-06-register-step-1-endpoint.md](step-06-register-step-1-endpoint.md)
