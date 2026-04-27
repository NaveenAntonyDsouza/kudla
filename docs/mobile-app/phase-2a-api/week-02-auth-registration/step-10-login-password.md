# Step 10 — `POST /api/v1/auth/login/password`

## Goal
Email + password login. Returns Sanctum token + user profile snapshot.

## Prerequisites
- [ ] [step-09 — email OTP](step-09-email-otp-endpoints.md) complete

## Procedure

### 1. Add `loginPassword` to `AuthController`

Append to `app/Http/Controllers/Api/V1/AuthController.php`:

```php
/**
 * Log in with email + password.
 *
 * @unauthenticated
 * @group Authentication
 */
public function loginPassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
        'device_name' => 'nullable|string|max:60',
    ]);

    $user = $this->auth->authenticatePassword($data['email'], $data['password']);

    if (! $user) {
        return ApiResponse::error('UNAUTHENTICATED', 'Invalid email or password.', status: 401);
    }

    if ($user->profile?->suspension_status && $user->profile->suspension_status !== 'active') {
        return ApiResponse::error('PROFILE_SUSPENDED', 'This account is suspended. Contact support.', status: 403);
    }

    $token = $this->auth->issueToken(
        $user,
        $data['device_name'] ?? 'Mobile',
        'password',
    );

    return ApiResponse::ok([
        'token' => $token,
        'user' => new \App\Http\Resources\V1\UserResource($user),
        'profile' => [
            'matri_id' => $user->profile?->matri_id,
            'onboarding_completed' => (bool) ($user->profile?->onboarding_completed),
            'onboarding_step_completed' => $user->profile?->onboarding_step_completed ?? 0,
            'profile_completion_pct' => $user->profile?->profile_completion_pct ?? 0,
        ],
        'membership' => $this->userMembershipSnapshot($user),
        'next_step' => $this->computeNextStep($user),
    ]);
}

private function userMembershipSnapshot(User $user): array
{
    $membership = $user->activeMembership ?? null;  // add this relation to User if missing
    return [
        'plan' => $membership?->plan?->title ?? 'Free',
        'ends_at' => $membership?->ends_at?->toIso8601String(),
        'is_premium' => (bool) $membership,
    ];
}
```

> **Note:** if `activeMembership` relation doesn't exist on User model, add:
> ```php
> public function activeMembership()
> {
>     return $this->hasOne(UserMembership::class)
>         ->where('is_active', true)
>         ->where('ends_at', '>', now());
> }
> ```

### 2. Register route

```php
Route::post('/auth/login/password', [\App\Http\Controllers\Api\V1\AuthController::class, 'loginPassword'])
    ->middleware('throttle:10,1');
```

### 3. Test

```bash
curl -X POST http://localhost:8000/api/v1/auth/login/password \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"naveen-test@example.com","password":"password","device_name":"Pixel 8 Pro"}'
```

Expected: envelope with token + user + profile + membership + next_step.

### 4. Pest tests

Create `tests/Feature/Api/V1/Auth/LoginPasswordTest.php`:

```php
<?php

use App\Models\User;
use function Pest\Laravel\postJson;

it('logs in with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('secret123'),
        'is_active' => true,
    ]);

    $response = postJson('/api/v1/auth/login/password', [
        'email' => 'login@example.com',
        'password' => 'secret123',
        'device_name' => 'Pixel',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user', 'profile', 'membership', 'next_step']]);
});

it('rejects wrong password', function () {
    User::factory()->create([
        'email' => 'login2@example.com',
        'password' => bcrypt('correct'),
    ]);

    $response = postJson('/api/v1/auth/login/password', [
        'email' => 'login2@example.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

it('rejects suspended account', function () {
    $user = User::factory()->create([
        'email' => 'suspended@example.com',
        'password' => bcrypt('secret'),
    ]);
    $user->profile?->update(['suspension_status' => 'banned']);

    $response = postJson('/api/v1/auth/login/password', [
        'email' => 'suspended@example.com',
        'password' => 'secret',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'PROFILE_SUSPENDED');
});
```

## Verification

- [ ] Curl returns envelope with token
- [ ] 3 tests pass
- [ ] `last_login_at` is updated on login
- [ ] `LoginHistory` row created with type='password'
- [ ] Rate limit: 11th login in 1 min returns 429

## Commit

```bash
git add app/Http/Controllers/Api/V1/AuthController.php app/Models/User.php routes/api.php tests/Feature/Api/V1/Auth/LoginPasswordTest.php
git commit -m "phase-2a wk-02: step-10 POST /auth/login/password"
```

## Next step
→ [step-11-login-phone-otp.md](step-11-login-phone-otp.md)
