# Step 13 — Forgot + Reset Password Endpoints

## Goal
`POST /auth/password/forgot` (sends reset email) and `POST /auth/password/reset` (consumes reset token). Uses Laravel's built-in `Password` broker — same as the web reset flow.

## Prerequisites
- [ ] [step-12 — email OTP login](step-12-login-email-otp.md) complete

## Procedure

### 1. Add methods to `AuthController`

```php
use Illuminate\Support\Facades\Password;

/**
 * Send a password reset link to the user's email.
 * Always returns success (never leak whether email exists).
 *
 * @unauthenticated
 * @group Authentication
 */
public function forgotPassword(Request $request): JsonResponse
{
    $data = $request->validate(['email' => 'required|email']);

    Password::sendResetLink($data);  // silently no-ops if email doesn't exist

    return ApiResponse::ok([
        'sent' => true,
        'message' => 'If that email is registered, a password reset link is on its way.',
    ]);
}

/**
 * Reset password using the token from the reset email.
 *
 * @unauthenticated
 * @group Authentication
 */
public function resetPassword(Request $request): JsonResponse
{
    $minPwd = config('matrimony.password_min_length', 6);
    $maxPwd = config('matrimony.password_max_length', 14);

    $data = $request->validate([
        'token' => 'required|string',
        'email' => 'required|email',
        'password' => "required|string|min:{$minPwd}|max:{$maxPwd}|confirmed",
    ]);

    $status = Password::reset($data, function ($user, $password) {
        $user->update(['password' => bcrypt($password)]);
        // Revoke all tokens — force re-login on all devices
        $this->auth->revokeAllTokens($user);
    });

    if ($status !== Password::PASSWORD_RESET) {
        return ApiResponse::error(
            'VALIDATION_FAILED',
            'The reset link is invalid or expired.',
            fields: ['token' => [__($status)]],
            status: 422,
        );
    }

    return ApiResponse::ok(['reset' => true]);
}
```

### 2. Register routes

```php
Route::post('/auth/password/forgot', [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1');

Route::post('/auth/password/reset', [\App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1');
```

### 3. Customize reset email (mobile deep link)

The default reset email links to `<APP_URL>/reset-password/<token>?email=...`. We want this URL to **also** open the Flutter app via App Links.

Edit `app/Providers/AppServiceProvider.php` `boot()`:

```php
use Illuminate\Auth\Notifications\ResetPassword;

public function boot(): void
{
    // ... existing

    // Point password reset URL at web route; App Links (Week 11 of Flutter) will
    // intercept this URL and open the app if installed.
    ResetPassword::createUrlUsing(function ($user, $token) {
        $email = urlencode($user->email);
        return config('app.url') . "/reset-password/{$token}?email={$email}";
    });
}
```

### 4. Test

```bash
curl -X POST http://localhost:8000/api/v1/auth/password/forgot \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"naveen@example.com"}'
# Expect: {"success":true,"data":{"sent":true,...}}

# Check mailbox (or laravel.log in local)
# Extract the token from the URL

curl -X POST http://localhost:8000/api/v1/auth/password/reset \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "token":"<TOKEN>",
    "email":"naveen@example.com",
    "password":"newpass123",
    "password_confirmation":"newpass123"
  }'
# Expect: {"success":true,"data":{"reset":true}}
```

### 5. Pest test

Create `tests/Feature/Api/V1/Auth/PasswordResetTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;
use function Pest\Laravel\postJson;

it('sends reset link', function () {
    User::factory()->create(['email' => 'r1@example.com']);

    postJson('/api/v1/auth/password/forgot', ['email' => 'r1@example.com'])
        ->assertOk()
        ->assertJsonPath('data.sent', true);
});

it('returns success for unknown email (no leak)', function () {
    postJson('/api/v1/auth/password/forgot', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('data.sent', true);
});

it('resets password with valid token', function () {
    $user = User::factory()->create(['email' => 'r2@example.com']);
    $token = Password::createToken($user);

    postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => 'r2@example.com',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertOk()->assertJsonPath('data.reset', true);

    // Verify we can log in with new password
    postJson('/api/v1/auth/login/password', [
        'email' => 'r2@example.com',
        'password' => 'newpass123',
    ])->assertOk();
});

it('rejects invalid token', function () {
    User::factory()->create(['email' => 'r3@example.com']);

    postJson('/api/v1/auth/password/reset', [
        'token' => 'invalid-token',
        'email' => 'r3@example.com',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertStatus(422)
      ->assertJsonPath('error.code', 'VALIDATION_FAILED');
});
```

## Verification

- [ ] Forgot endpoint sends an email in local dev (check laravel.log)
- [ ] Reset endpoint accepts valid token, rejects invalid
- [ ] After reset, all existing tokens are revoked
- [ ] 4 Pest tests pass

## Commit

```bash
git add app/Http/Controllers/Api/V1/AuthController.php app/Providers/AppServiceProvider.php routes/api.php tests/Feature/Api/V1/Auth/PasswordResetTest.php
git commit -m "phase-2a wk-02: step-13 forgot + reset password endpoints"
```

## Next step
→ [step-14-me-logout.md](step-14-me-logout.md)
