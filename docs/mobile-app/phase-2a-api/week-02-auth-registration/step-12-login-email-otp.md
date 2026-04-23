# Step 12 — Login via Email OTP

## Goal
Alternative login using email + OTP. Same pattern as step 11, email channel.

## Prerequisites
- [ ] [step-11 — phone OTP login](step-11-login-phone-otp.md) complete

## Procedure

### 1. Add feature toggle to `sendEmailOtp`

In `AuthController::sendEmailOtp`, add the feature-toggle guard when `purpose=login`:

```php
if ($data['purpose'] === 'login') {
    $enabled = \App\Models\SiteSetting::getValue('email_otp_login_enabled', '0') === '1';
    if (! $enabled) {
        return ApiResponse::error(
            'UNAUTHORIZED',
            'Email OTP login is currently disabled.',
            status: 403,
        );
    }
}
```

> **Note:** default is `'0'` (disabled) for email OTP login. Phone OTP login default is `'1'`. Admin can flip both from Site Settings.

### 2. Test

```bash
# Admin sets email_otp_login_enabled=1 first (or tinker):
php artisan tinker
>>> \App\Models\SiteSetting::set('email_otp_login_enabled', '1');

# Then:
curl -X POST http://localhost:8000/api/v1/auth/otp/email/send \
  -d '{"email":"test@example.com","purpose":"login"}' \
  -H "Content-Type: application/json" -H "Accept: application/json"

curl -X POST http://localhost:8000/api/v1/auth/otp/email/verify \
  -d '{"email":"test@example.com","otp":"123456","purpose":"login","device_name":"Pixel"}' \
  -H "Content-Type: application/json" -H "Accept: application/json"
# Expect: token
```

### 3. Pest test

Create `tests/Feature/Api/V1/Auth/LoginEmailOtpTest.php`:

```php
<?php

use App\Models\SiteSetting;
use App\Models\User;
use function Pest\Laravel\postJson;

beforeEach(function () {
    SiteSetting::set('email_otp_login_enabled', '1');
});

it('logs in via email OTP', function () {
    User::factory()->create(['email' => 'otpl@example.com']);

    postJson('/api/v1/auth/otp/email/send', ['email' => 'otpl@example.com', 'purpose' => 'login'])->assertOk();

    $response = postJson('/api/v1/auth/otp/email/verify', [
        'email' => 'otpl@example.com',
        'otp' => '123456',
        'purpose' => 'login',
        'device_name' => 'Pixel',
    ]);

    $response->assertOk()->assertJsonStructure(['data' => ['token']]);
});

it('blocks when email OTP login disabled', function () {
    SiteSetting::set('email_otp_login_enabled', '0');

    postJson('/api/v1/auth/otp/email/send', ['email' => 'a@b.com', 'purpose' => 'login'])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});
```

## Verification

- [ ] Two-step curl flow works when enabled
- [ ] Feature toggle blocks when disabled
- [ ] Tests pass

## Commit

```bash
git add app/Http/Controllers/Api/V1/AuthController.php tests/Feature/Api/V1/Auth/LoginEmailOtpTest.php
git commit -m "phase-2a wk-02: step-12 email OTP login with feature toggle"
```

## Next step
→ [step-13-forgot-reset-password.md](step-13-forgot-reset-password.md)
