# Step 11 — Login via Phone OTP

## Goal
Alternative login using phone + OTP. Reuses the existing `/auth/otp/phone/send` + `/auth/otp/phone/verify` endpoints with `purpose=login`.

## Prerequisites
- [ ] [step-10 — login/password](step-10-login-password.md) complete

## Procedure

### 1. Respect site feature toggle

Login via phone OTP is already covered by `verifyPhoneOtp(purpose=login)` from step 8. We just need to enforce the `mobile_otp_login_enabled` setting.

Update `sendPhoneOtp` in `AuthController` to check feature toggle when `purpose=login`:

```php
public function sendPhoneOtp(Request $request): JsonResponse
{
    $data = $request->validate([...]);

    // NEW: feature toggle for login via phone OTP
    if ($data['purpose'] === 'login') {
        $enabled = \App\Models\SiteSetting::getValue('mobile_otp_login_enabled', '1') === '1';
        if (! $enabled) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'Mobile OTP login is currently disabled.',
                status: 403,
            );
        }
    }

    // ... rest unchanged
}
```

### 2. No new route needed

Phone OTP login = two-step:
1. `POST /auth/otp/phone/send` with `{"phone":"...","purpose":"login"}`
2. `POST /auth/otp/phone/verify` with `{"phone":"...","otp":"...","purpose":"login","device_name":"..."}` → returns token

Flutter will call these two endpoints in sequence.

### 3. Test

```bash
# Step 1
curl -X POST http://localhost:8000/api/v1/auth/otp/phone/send \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"phone":"9876500010","purpose":"login"}'

# Step 2
curl -X POST http://localhost:8000/api/v1/auth/otp/phone/verify \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"phone":"9876500010","otp":"123456","purpose":"login","device_name":"Pixel"}'
# Expect: token in response
```

### 4. Pest test

Create `tests/Feature/Api/V1/Auth/LoginPhoneOtpTest.php`:

```php
<?php

use App\Models\SiteSetting;
use App\Models\User;
use function Pest\Laravel\postJson;

beforeEach(function () {
    SiteSetting::set('mobile_otp_login_enabled', '1');
});

it('logs in via phone OTP', function () {
    $user = User::factory()->create(['phone' => '9876500040']);

    postJson('/api/v1/auth/otp/phone/send', [
        'phone' => '9876500040',
        'purpose' => 'login',
    ])->assertOk();

    $response = postJson('/api/v1/auth/otp/phone/verify', [
        'phone' => '9876500040',
        'otp' => '123456',
        'purpose' => 'login',
        'device_name' => 'Pixel 8',
    ]);

    $response->assertOk()->assertJsonStructure(['data' => ['token', 'user', 'profile', 'next_step']]);
});

it('blocks when feature disabled', function () {
    SiteSetting::set('mobile_otp_login_enabled', '0');

    $response = postJson('/api/v1/auth/otp/phone/send', [
        'phone' => '9876500041',
        'purpose' => 'login',
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'UNAUTHORIZED');
});
```

## Verification

- [ ] Two-step curl flow returns token
- [ ] Feature toggle respected
- [ ] Tests pass

## Commit

```bash
git add app/Http/Controllers/Api/V1/AuthController.php tests/Feature/Api/V1/Auth/LoginPhoneOtpTest.php
git commit -m "phase-2a wk-02: step-11 phone OTP login with feature toggle"
```

## Next step
→ [step-12-login-email-otp.md](step-12-login-email-otp.md)
