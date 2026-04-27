# Step 14 — `GET /auth/me` + `POST /auth/logout`

## Goal
Flutter calls `/auth/me` on every launch to validate the stored token. `/auth/logout` revokes the current token (not all tokens — only this device).

## Prerequisites
- [ ] [step-13 — password reset](step-13-forgot-reset-password.md) complete

## Procedure

### 1. Add methods to `AuthController`

```php
/**
 * Return the currently-authenticated user with profile + membership.
 *
 * Called by Flutter on every app launch to validate stored token.
 *
 * @group Authentication
 * @authenticated
 */
public function me(Request $request): JsonResponse
{
    $user = $request->user();

    return ApiResponse::ok([
        'user' => new \App\Http\Resources\V1\UserResource($user),
        'profile' => [
            'matri_id' => $user->profile?->matri_id,
            'full_name' => $user->profile?->full_name,
            'gender' => $user->profile?->gender,
            'onboarding_completed' => (bool) ($user->profile?->onboarding_completed),
            'onboarding_step_completed' => $user->profile?->onboarding_step_completed ?? 0,
            'profile_completion_pct' => $user->profile?->profile_completion_pct ?? 0,
            'is_approved' => (bool) ($user->profile?->is_approved),
            'is_verified' => (bool) ($user->profile?->is_verified),
            'is_hidden' => (bool) ($user->profile?->is_hidden),
            'primary_photo_url' => $user->profile?->primaryPhoto?->medium_url ?? null,
        ],
        'membership' => $this->userMembershipSnapshot($user),
        'next_step' => $this->computeNextStep($user),
    ]);
}

/**
 * Revoke the currently-used token.
 *
 * @group Authentication
 * @authenticated
 */
public function logout(Request $request): JsonResponse
{
    $this->auth->revokeCurrentToken($request->user());
    return ApiResponse::ok(['logged_out' => true]);
}
```

### 2. Register routes

In the `auth:sanctum` group in `routes/api.php`:

```php
Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
```

### 3. Test

```bash
# Login to get token
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login/password \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"naveen-test@example.com","password":"password"}' | jq -r '.data.token')

# Me
curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/me | jq '.data | keys'
# Expect: ["membership","next_step","profile","user"]

# Logout
curl -s -X POST -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/logout | jq
# Expect: {"success":true,"data":{"logged_out":true}}

# Me again (should 401)
curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/me | jq '.error.code'
# Expect: "UNAUTHENTICATED"
```

### 4. Pest tests

Create `tests/Feature/Api/V1/Auth/MeAndLogoutTest.php`:

```php
<?php

use App\Models\User;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('returns current user via /me', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['user', 'profile', 'membership', 'next_step']])
        ->assertJsonPath('data.user.id', $user->id);
});

it('rejects /me without token', function () {
    getJson('/api/v1/auth/me')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

it('logout revokes current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.logged_out', true);

    // Token should no longer work
    getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
        ->assertStatus(401);
});

it('logout revokes only the current token, not all', function () {
    $user = User::factory()->create();
    $token1 = $user->createToken('device-1')->plainTextToken;
    $token2 = $user->createToken('device-2')->plainTextToken;

    // Logout using token1
    postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer $token1"])
        ->assertOk();

    // token1 revoked
    getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token1"])
        ->assertStatus(401);

    // token2 still works
    getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token2"])
        ->assertOk();
});
```

## Verification

- [ ] Curl flow: login → /me → logout → /me (401)
- [ ] 4 tests pass
- [ ] Multi-device: logout on one device leaves others signed in

## Commit

```bash
git add app/Http/Controllers/Api/V1/AuthController.php routes/api.php tests/Feature/Api/V1/Auth/MeAndLogoutTest.php
git commit -m "phase-2a wk-02: step-14 GET /auth/me + POST /auth/logout"
```

## Next step
→ [step-15-device-registration.md](step-15-device-registration.md)
