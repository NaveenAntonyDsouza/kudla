# Step 6 — `POST /api/v1/auth/register/step-1` Endpoint

## Goal
First authentication-ish endpoint. Creates a User + Profile, issues a Sanctum token, returns it for steps 2–5 to use.

## Prerequisites
- [ ] [step-05 — FormRequest base](step-05-api-form-request-pattern.md) complete

## Procedure

### 1. Create `RegistrationController`

Create `app/Http/Controllers/Api/V1/RegistrationController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Auth\RegisterStep1Request;
use App\Http\Requests\Api\V1\Auth\RegisterStep2Request;
use App\Http\Requests\Api\V1\Auth\RegisterStep3Request;
use App\Http\Requests\Api\V1\Auth\RegisterStep4Request;
use App\Http\Requests\Api\V1\Auth\RegisterStep5Request;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends BaseApiController
{
    public function __construct(private RegistrationService $reg) {}

    /**
     * Register a new account (step 1 of 5).
     *
     * Creates User + Profile, returns Sanctum token.
     *
     * @unauthenticated
     * @group Authentication
     */
    public function step1(RegisterStep1Request $request): JsonResponse
    {
        $result = $this->reg->createFreeAccount($request->validated(), $request);
        $token = $result['user']->createToken('api-register')->plainTextToken;

        return ApiResponse::created([
            'user' => new UserResource($result['user']),
            'profile' => [
                'matri_id' => $result['profile']->matri_id,
                'onboarding_step_completed' => 1,
                'onboarding_completed' => false,
                'is_approved' => (bool) $result['profile']->is_approved,
            ],
            'token' => $token,
            'next_step' => 'register.step-2',
        ]);
    }

    // step2, step3, step4, step5 methods come in step-07
}
```

### 2. Create `UserResource`

Create `app/Http/Resources/V1/UserResource.php`:

```php
<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'branch_id' => $this->branch_id,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'role' => $this->role,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
```

### 3. Register route

In `routes/api.php`:

```php
// Add under public routes:
Route::post('/auth/register/step-1', [
    \App\Http\Controllers\Api\V1\RegistrationController::class,
    'step1',
]);
```

### 4. Test with curl

```bash
curl -X POST http://localhost:8000/api/v1/auth/register/step-1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test1@example.com",
    "phone": "9999900001",
    "password": "password",
    "password_confirmation": "password",
    "gender": "Male",
    "date_of_birth": "1995-04-12"
  }'
```

Expected 201:
```json
{
  "success": true,
  "data": {
    "user": { "id": ..., "email": "test1@example.com" },
    "profile": { "matri_id": "AM100...", "onboarding_step_completed": 1 },
    "token": "1|abc...xyz",
    "next_step": "register.step-2"
  }
}
```

### 5. Use the token to hit a protected endpoint

```bash
TOKEN="<paste-token-from-above>"
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/ping
```

✓ Should return authenticated response.

### 6. Pest test

Create `tests/Feature/Api/V1/Auth/RegisterStep1Test.php`:

```php
<?php

use App\Models\Profile;
use App\Models\User;
use function Pest\Laravel\postJson;

it('registers a new user and returns token', function () {
    $response = postJson('/api/v1/auth/register/step-1', [
        'full_name' => 'Naveen D\'Souza',
        'email' => 'naveen-test@example.com',
        'phone' => '9876500001',
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'Male',
        'date_of_birth' => '1995-04-12',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['user' => ['id', 'email'], 'profile' => ['matri_id'], 'token', 'next_step'],
        ])
        ->assertJson(['success' => true, 'data' => ['next_step' => 'register.step-2']]);

    expect(User::where('email', 'naveen-test@example.com')->exists())->toBeTrue();
    expect(Profile::where('full_name', 'Naveen D\'Souza')->exists())->toBeTrue();
});

it('rejects duplicate phone', function () {
    User::factory()->create(['phone' => '9876500002']);

    $response = postJson('/api/v1/auth/register/step-1', [
        'full_name' => 'Test',
        'email' => 'new@example.com',
        'phone' => '9876500002',
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'Female',
        'date_of_birth' => '1995-04-12',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.fields.phone.0', 'This phone number is already registered. Try logging in.');
});

it('captures affiliate ref', function () {
    // TODO: after AffiliateTracker is confirmed to accept ref via body param
    // This test is a placeholder — verify the mechanism in step-14
});
```

Run:
```bash
./vendor/bin/pest --filter=RegisterStep1
```

## Verification

- [ ] curl returns envelope 201 with token
- [ ] Pest tests pass
- [ ] Token from response works for `/auth/ping` protected endpoint
- [ ] Duplicate email and phone are rejected with clear messages
- [ ] Underage DOB is rejected

## Common issues

| Issue | Fix |
|-------|-----|
| `Call to undefined method UserResource::resolve()` | JsonResource's `resolve()` is available but we pass instance, not class. `new UserResource($user)` is correct |
| Token returned but `/auth/ping` returns 401 | Check `HasApiTokens` trait on User model (step 01 of Week 1) |
| `matri_id` is null | Check Profile model — may need to generate in `booted()` observer. If not present, add: `$profile->matri_id = 'AM' . str_pad($profile->id + 100000, 6, '0', STR_PAD_LEFT); $profile->save();` |

## Commit

```bash
git add app/Http/Controllers/Api/V1/RegistrationController.php app/Http/Resources/V1/UserResource.php routes/api.php tests/Feature/Api/V1/Auth/
git commit -m "phase-2a wk-02: step-06 POST /auth/register/step-1 endpoint"
```

## Next step
→ [step-07-register-steps-2-5.md](step-07-register-steps-2-5.md)
