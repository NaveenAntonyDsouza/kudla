# Step 3 — Extract `AuthService`

## Goal
Move login + token lifecycle logic out of `LoginController` into a reusable service. Both web and API layers will call it.

## Prerequisites
- [ ] [step-02 — OTP service refactor](step-02-otp-service-refactor.md) complete

## Procedure

### 1. Create the service

Create `app/Services/AuthService.php`:

```php
<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Authenticate via email + password.
     * Returns the User on success, null on failure.
     */
    public function authenticatePassword(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }
        if (! $user->is_active) {
            return null;  // suspended account
        }
        return $user;
    }

    /**
     * Authenticate via phone + OTP. Caller must have already called OtpService::send.
     */
    public function authenticatePhoneOtp(string $phone, string $otp): ?User
    {
        if (! $this->otpService->verify($phone, OtpService::CHANNEL_PHONE, $otp)) {
            return null;
        }
        $user = User::where('phone', $phone)->first();
        return $user && $user->is_active ? $user : null;
    }

    /**
     * Authenticate via email + OTP.
     */
    public function authenticateEmailOtp(string $email, string $otp): ?User
    {
        if (! $this->otpService->verify($email, OtpService::CHANNEL_EMAIL, $otp)) {
            return null;
        }
        $user = User::where('email', $email)->first();
        return $user && $user->is_active ? $user : null;
    }

    /**
     * Issue a Sanctum personal access token for the user.
     * Also updates last_login_at + resets reengagement_level + logs LoginHistory.
     */
    public function issueToken(User $user, string $deviceName, string $loginType): string
    {
        $user->update([
            'last_login_at' => now(),
            'reengagement_level' => 0,
        ]);

        LoginHistory::record($user, $loginType);

        return $user->createToken($deviceName)->plainTextToken;
    }

    /**
     * Revoke the currently-used token (from $request->user()).
     */
    public function revokeCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }
    }

    /**
     * Revoke ALL tokens for a user. Used when password changes.
     */
    public function revokeAllTokens(User $user): int
    {
        $count = $user->tokens()->count();
        $user->tokens()->delete();
        return $count;
    }
}
```

### 2. Refactor `LoginController` to use `AuthService`

Open `app/Http/Controllers/Auth/LoginController.php`. Replace the `login()` method body:

```php
public function login(Request $request, \App\Services\AuthService $auth)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = $auth->authenticatePassword($request->email, $request->password);
    if (! $user) {
        return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
    }

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();

    // Update last_login_at + LoginHistory via service
    $user->update([
        'last_login_at' => now(),
        'reengagement_level' => 0,
    ]);
    \App\Models\LoginHistory::record($user, 'password');

    // Existing onboarding redirect logic...
    if ($user->profile && ! $user->profile->onboarding_completed) {
        $step = $user->profile->onboarding_step_completed;
        if ($step >= 5) return redirect()->route('register.verifyemail');
        return redirect()->route('register.step'.($step + 1));
    }

    return redirect()->intended('/dashboard');
}
```

Similar tightening for `sendLoginOtp`, `verifyLoginOtp`, etc. — delegate to `AuthService` where possible.

> **⚠️ Don't break web.** Test the web login flow manually after refactoring.

### 3. Write Pest tests

Create `tests/Unit/Services/AuthServiceTest.php`:

```php
<?php

use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;

it('authenticates with correct password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct'),
        'is_active' => true,
    ]);

    $result = app(AuthService::class)->authenticatePassword($user->email, 'correct');

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($user->id);
});

it('rejects wrong password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct'),
        'is_active' => true,
    ]);

    $result = app(AuthService::class)->authenticatePassword($user->email, 'wrong');

    expect($result)->toBeNull();
});

it('rejects suspended account', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct'),
        'is_active' => false,
    ]);

    $result = app(AuthService::class)->authenticatePassword($user->email, 'correct');

    expect($result)->toBeNull();
});

it('issues a Sanctum token', function () {
    $user = User::factory()->create();

    $token = app(AuthService::class)->issueToken($user, 'iPhone 14', 'password');

    expect($token)->toBeString()->not->toBeEmpty();
    expect($user->tokens()->count())->toBe(1);
});

it('revoke all tokens', function () {
    $user = User::factory()->create();
    $user->createToken('a');
    $user->createToken('b');

    $count = app(AuthService::class)->revokeAllTokens($user);

    expect($count)->toBe(2);
    expect($user->tokens()->count())->toBe(0);
});
```

### 4. Run tests

```bash
./vendor/bin/pest --filter="AuthService|OtpService"
```

### 5. Manual web smoke test

- Log in via web `/login` with email + password → works
- Log in via web with phone OTP → works

## Verification

- [ ] All 5 Pest tests pass
- [ ] Web login still works (manual)
- [ ] `LoginController` references `AuthService` via DI (or by direct instantiation — matter of style)

## Commit

```bash
git add app/Services/AuthService.php app/Http/Controllers/Auth/LoginController.php tests/Unit/Services/AuthServiceTest.php
git commit -m "phase-2a wk-02: step-03 extract AuthService for shared use across web+API"
```

## Next step
→ [step-04-extract-registration-service.md](step-04-extract-registration-service.md)
