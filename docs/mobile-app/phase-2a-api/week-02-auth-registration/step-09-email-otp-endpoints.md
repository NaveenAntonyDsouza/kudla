# Step 9 — Email OTP Send + Verify Endpoints

## Goal
Mirror of phone OTP for email. Same signature, different channel.

## Prerequisites
- [ ] [step-08 — phone OTP endpoints](step-08-phone-otp-endpoints.md) complete

## Procedure

### 1. Add methods to `AuthController`

Append to `app/Http/Controllers/Api/V1/AuthController.php`:

```php
/**
 * Send an email OTP.
 *
 * @unauthenticated
 * @group Authentication
 */
public function sendEmailOtp(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => 'required|email',
        'purpose' => 'required|in:register,login,reset',
    ]);

    if (in_array($data['purpose'], ['login', 'reset'], true)) {
        if (! User::where('email', $data['email'])->exists()) {
            return ApiResponse::ok(['sent' => true, 'expires_in_seconds' => 600, 'cooldown_seconds' => 30]);
        }
    }

    $this->otp->send($data['email'], OtpService::CHANNEL_EMAIL);

    return ApiResponse::ok([
        'sent' => true,
        'expires_in_seconds' => 600,
        'cooldown_seconds' => 30,
    ]);
}

/**
 * Verify an email OTP.
 *
 * @unauthenticated
 * @group Authentication
 */
public function verifyEmailOtp(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => 'required|email',
        'otp' => 'required|digits:6',
        'purpose' => 'required|in:register,login,reset',
        'device_name' => 'nullable|string|max:60',
    ]);

    if (! $this->otp->verify($data['email'], OtpService::CHANNEL_EMAIL, $data['otp'])) {
        return ApiResponse::error('OTP_INVALID', 'Invalid or expired OTP.', status: 422);
    }

    $user = User::where('email', $data['email'])->first();
    if (! $user) {
        return ApiResponse::error('NOT_FOUND', 'No account found with this email.', status: 404);
    }

    return match ($data['purpose']) {
        'register' => $this->handleEmailRegisterVerify($user),
        'login' => $this->handleLoginVerify($user, $data['device_name'] ?? 'Mobile', 'email_otp'),
        'reset' => $this->handleResetVerify($user),
    };
}

private function handleEmailRegisterVerify(User $user): JsonResponse
{
    $user->update(['email_verified_at' => now()]);
    $nextStep = $this->reg->nextVerificationStep($user);

    return ApiResponse::ok([
        'verified' => true,
        'user' => ['email_verified_at' => $user->email_verified_at->toIso8601String()],
        'onboarding_completed' => (bool) $user->profile?->onboarding_completed,
        'next_step' => $nextStep === 'complete' ? 'dashboard' : $nextStep,
    ]);
}
```

### 2. Register routes

In `routes/api.php`:

```php
Route::post('/auth/otp/email/send', [\App\Http\Controllers\Api\V1\AuthController::class, 'sendEmailOtp'])
    ->middleware('throttle:5,1');

Route::post('/auth/otp/email/verify', [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyEmailOtp'])
    ->middleware('throttle:10,1');
```

### 3. Test

```bash
# Send email OTP
curl -X POST http://localhost:8000/api/v1/auth/otp/email/send \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"test@example.com","purpose":"register"}'

# Check laravel.log for "DEV OTP [email] for test@example.com: 123456"

# Verify
curl -X POST http://localhost:8000/api/v1/auth/otp/email/verify \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"test@example.com","otp":"123456","purpose":"register"}'
```

### 4. Pest test

Create `tests/Feature/Api/V1/Auth/EmailOtpTest.php`:

```php
<?php

use App\Models\User;
use function Pest\Laravel\postJson;

it('sends email OTP', function () {
    User::factory()->create(['email' => 'em@example.com']);

    $response = postJson('/api/v1/auth/otp/email/send', [
        'email' => 'em@example.com',
        'purpose' => 'register',
    ]);

    $response->assertOk()->assertJsonPath('data.sent', true);
});

it('marks email_verified_at after register verify', function () {
    $user = User::factory()->create(['email' => 'em2@example.com', 'email_verified_at' => null]);

    postJson('/api/v1/auth/otp/email/send', ['email' => 'em2@example.com', 'purpose' => 'register']);

    $response = postJson('/api/v1/auth/otp/email/verify', [
        'email' => 'em2@example.com',
        'otp' => '123456',
        'purpose' => 'register',
    ]);

    $response->assertOk()->assertJsonPath('data.verified', true);
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});
```

## Verification

- [ ] Both endpoints work via curl
- [ ] Laravel log shows email OTP in dev
- [ ] Pest tests pass
- [ ] Rate limit in effect (5 sends/min)

## Commit

```bash
git add app/Http/Controllers/Api/V1/AuthController.php routes/api.php tests/Feature/Api/V1/Auth/EmailOtpTest.php
git commit -m "phase-2a wk-02: step-09 email OTP send + verify endpoints"
```

## Next step
→ [step-10-login-password.md](step-10-login-password.md)
