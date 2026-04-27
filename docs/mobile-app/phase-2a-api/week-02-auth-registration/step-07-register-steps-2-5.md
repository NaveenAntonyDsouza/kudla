# Step 7 — Register Steps 2–5 Endpoints (Authenticated)

## Goal
Complete the 5-step registration flow. Steps 2–5 require a Bearer token (obtained from step 1). Each updates one aspect of the profile and advances `onboarding_step_completed`.

## Prerequisites
- [ ] [step-06 — step 1 endpoint](step-06-register-step-1-endpoint.md) complete

## Procedure

### 1. Add step2–step5 methods to `RegistrationController`

Append to `app/Http/Controllers/Api/V1/RegistrationController.php`:

```php
/**
 * Register step 2: primary + religious + family info.
 *
 * @group Authentication
 * @authenticated
 */
public function step2(RegisterStep2Request $request): JsonResponse
{
    $profile = $request->user()->profile;
    abort_if(! $profile, 422);

    $this->reg->updateStep2($profile, $request->validated());

    return ApiResponse::ok([
        'profile' => [
            'matri_id' => $profile->matri_id,
            'onboarding_step_completed' => 2,
        ],
        'next_step' => 'register.step-3',
    ]);
}

/**
 * Register step 3: education + professional.
 *
 * @group Authentication
 * @authenticated
 */
public function step3(RegisterStep3Request $request): JsonResponse
{
    $this->reg->updateStep3($request->user()->profile, $request->validated());

    return ApiResponse::ok([
        'profile' => ['onboarding_step_completed' => 3],
        'next_step' => 'register.step-4',
    ]);
}

/**
 * Register step 4: location + contact.
 *
 * @group Authentication
 * @authenticated
 */
public function step4(RegisterStep4Request $request): JsonResponse
{
    $this->reg->updateStep4($request->user()->profile, $request->validated());

    return ApiResponse::ok([
        'profile' => ['onboarding_step_completed' => 4],
        'next_step' => 'register.step-5',
    ]);
}

/**
 * Register step 5: creator info + verification-next-step.
 *
 * @group Authentication
 * @authenticated
 */
public function step5(RegisterStep5Request $request): JsonResponse
{
    $nextStep = $this->reg->finalizeStep5($request->user()->profile, $request->validated());

    return ApiResponse::ok([
        'profile' => ['onboarding_step_completed' => 5],
        'next_step' => $nextStep,  // 'verify.email' | 'verify.phone' | 'complete'
        'email_verification_enabled' => \App\Models\SiteSetting::getValue('email_verification_enabled', '1') === '1',
        'phone_verification_enabled' => \App\Models\SiteSetting::getValue('phone_verification_enabled', '0') === '1',
        'user' => [
            'email_verified_at' => $request->user()->email_verified_at?->toIso8601String(),
            'phone_verified_at' => $request->user()->phone_verified_at?->toIso8601String(),
        ],
    ]);
}
```

### 2. Register routes

In `routes/api.php`, under the `auth:sanctum` group:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/register/step-2', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step2']);
    Route::post('/auth/register/step-3', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step3']);
    Route::post('/auth/register/step-4', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step4']);
    Route::post('/auth/register/step-5', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step5']);

    // ping placeholder from week 1:
    Route::get('/auth/ping', fn (\Illuminate\Http\Request $r) => \App\Http\Responses\ApiResponse::ok(['user_id' => $r->user()->id]));
});
```

### 3. Test full registration flow

```bash
# Step 1: Register
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/register/step-1 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "full_name": "Full Flow",
    "email": "flow@example.com",
    "phone": "9876500010",
    "password": "password",
    "password_confirmation": "password",
    "gender": "Male",
    "date_of_birth": "1995-04-12"
  }' | jq -r '.data.token')

echo "Token: $TOKEN"

# Step 2: Primary + religious
curl -s -X POST http://localhost:8000/api/v1/auth/register/step-2 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "height": 170,
    "complexion": "Wheatish",
    "body_type": "Average",
    "physical_status": "Normal",
    "marital_status": "Never Married",
    "family_status": "Middle Class",
    "religion": "Hindu",
    "caste": "Brahmin"
  }' | jq '.data.next_step'
# Expect: "register.step-3"

# Step 3: Education
curl -s -X POST http://localhost:8000/api/v1/auth/register/step-3 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "education_level": "Bachelors",
    "educational_qualification": "BE",
    "occupation": "Software Professional"
  }' | jq '.data.next_step'

# Step 4: Location
curl -s -X POST http://localhost:8000/api/v1/auth/register/step-4 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "native_country": "India",
    "native_state": "Karnataka",
    "native_district": "Dakshina Kannada",
    "pin_zip_code": "575001",
    "whatsapp_number": "9876500010",
    "mobile_number": "9876500010"
  }' | jq '.data.next_step'

# Step 5: Finalize
curl -s -X POST http://localhost:8000/api/v1/auth/register/step-5 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "created_by": "Self",
    "how_did_you_hear_about_us": "Google"
  }' | jq '.data'
# Expect: next_step = "verify.email" or "verify.phone" or "complete"
```

### 4. Pest test

Create `tests/Feature/Api/V1/Auth/RegisterFullFlowTest.php`:

```php
<?php

use App\Models\User;
use function Pest\Laravel\postJson;

it('completes the full 5-step registration flow', function () {
    // Step 1
    $r1 = postJson('/api/v1/auth/register/step-1', [
        'full_name' => 'Flow Test',
        'email' => 'flow@example.com',
        'phone' => '9876500020',
        'password' => 'password',
        'password_confirmation' => 'password',
        'gender' => 'Male',
        'date_of_birth' => '1995-04-12',
    ]);
    $r1->assertCreated();
    $token = $r1->json('data.token');

    $headers = ['Authorization' => "Bearer $token"];

    // Step 2
    postJson('/api/v1/auth/register/step-2', [
        'height' => 170,
        'complexion' => 'Wheatish',
        'body_type' => 'Average',
        'physical_status' => 'Normal',
        'marital_status' => 'Never Married',
        'family_status' => 'Middle Class',
        'religion' => 'Hindu',
        'caste' => 'Brahmin',
    ], $headers)->assertOk()->assertJsonPath('data.next_step', 'register.step-3');

    // Step 3
    postJson('/api/v1/auth/register/step-3', [
        'education_level' => 'Bachelors',
        'educational_qualification' => 'BE',
        'occupation' => 'Software Professional',
    ], $headers)->assertOk();

    // Step 4
    postJson('/api/v1/auth/register/step-4', [
        'native_country' => 'India',
        'native_state' => 'Karnataka',
    ], $headers)->assertOk();

    // Step 5
    $r5 = postJson('/api/v1/auth/register/step-5', [
        'created_by' => 'Self',
    ], $headers);
    $r5->assertOk();

    expect(in_array($r5->json('data.next_step'), ['verify.email', 'verify.phone', 'complete']))->toBeTrue();

    $user = User::where('email', 'flow@example.com')->first();
    expect($user->profile->onboarding_step_completed)->toBe(5);
});

it('rejects step 2 without token', function () {
    postJson('/api/v1/auth/register/step-2', [])
        ->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
});
```

## Verification

- [ ] Full curl flow from step 1 through step 5 works and returns correct next_step
- [ ] Pest test green
- [ ] `onboarding_step_completed` increments 1 → 2 → 3 → 4 → 5 in DB
- [ ] Tables populated: `profiles`, `religious_infos`, `family_details`, `education_details`, `location_infos`, `contact_infos`

## Commit

```bash
git add app/Http/Controllers/Api/V1/RegistrationController.php routes/api.php tests/Feature/Api/V1/Auth/RegisterFullFlowTest.php
git commit -m "phase-2a wk-02: step-07 register steps 2-5 endpoints"
```

## Next step
→ [step-08-phone-otp-endpoints.md](step-08-phone-otp-endpoints.md)
