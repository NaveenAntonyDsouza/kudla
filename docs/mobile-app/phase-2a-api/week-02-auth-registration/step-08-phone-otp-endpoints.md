# Step 8 — Phone OTP Send + Verify Endpoints

## Goal
Add `POST /api/v1/auth/otp/phone/send` and `POST /api/v1/auth/otp/phone/verify`. Used for registration verification + OTP-based login.

## Prerequisites
- [ ] [step-07 — register steps 2-5](step-07-register-steps-2-5.md) complete
- [ ] `OtpService::send()` and `::verify()` from step 02

## Procedure

### 1. Create AuthController

Create `app/Http/Controllers/Api/V1/AuthController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct(
        private OtpService $otp,
        private AuthService $auth,
        private RegistrationService $reg,
    ) {}

    /**
     * Send a phone OTP.
     *
     * @unauthenticated
     * @group Authentication
     */
    public function sendPhoneOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|digits:10',
            'purpose' => 'required|in:register,login,reset',
        ]);

        // For login/reset — ensure user exists
        if (in_array($data['purpose'], ['login', 'reset'], true)) {
            if (! User::where('phone', $data['phone'])->exists()) {
                // Don't leak existence — return success always
                return ApiResponse::ok(['sent' => true, 'expires_in_seconds' => 600, 'cooldown_seconds' => 30]);
            }
        }

        $this->otp->send($data['phone'], OtpService::CHANNEL_PHONE);

        return ApiResponse::ok([
            'sent' => true,
            'expires_in_seconds' => 600,
            'cooldown_seconds' => 30,
        ]);
    }

    /**
     * Verify a phone OTP.
     *
     * For purpose=register: marks user.phone_verified_at = now.
     * For purpose=login: returns a Sanctum token.
     * For purpose=reset: returns a short-lived reset token (handled in step-13).
     *
     * @unauthenticated
     * @group Authentication
     */
    public function verifyPhoneOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|digits:10',
            'otp' => 'required|digits:6',
            'purpose' => 'required|in:register,login,reset',
            'device_name' => 'nullable|string|max:60',
        ]);

        if (! $this->otp->verify($data['phone'], OtpService::CHANNEL_PHONE, $data['otp'])) {
            return ApiResponse::error('OTP_INVALID', 'Invalid or expired OTP.', status: 422);
        }

        $user = User::where('phone', $data['phone'])->first();
        if (! $user) {
            return ApiResponse::error('NOT_FOUND', 'No account found with this phone.', status: 404);
        }

        return match ($data['purpose']) {
            'register' => $this->handleRegisterVerify($user),
            'login' => $this->handleLoginVerify($user, $data['device_name'] ?? 'Mobile', 'phone_otp'),
            'reset' => $this->handleResetVerify($user),
        };
    }

    private function handleRegisterVerify(User $user): JsonResponse
    {
        $user->update(['phone_verified_at' => now()]);
        $nextStep = $this->reg->nextVerificationStep($user);

        return ApiResponse::ok([
            'verified' => true,
            'user' => ['phone_verified_at' => $user->phone_verified_at->toIso8601String()],
            'onboarding_completed' => (bool) $user->profile?->onboarding_completed,
            'next_step' => $nextStep === 'complete' ? 'dashboard' : $nextStep,
        ]);
    }

    private function handleLoginVerify(User $user, string $deviceName, string $loginType): JsonResponse
    {
        $token = $this->auth->issueToken($user, $deviceName, $loginType);

        return ApiResponse::ok([
            'token' => $token,
            'user' => new \App\Http\Resources\V1\UserResource($user),
            'profile' => [
                'matri_id' => $user->profile?->matri_id,
                'onboarding_step_completed' => $user->profile?->onboarding_step_completed ?? 0,
                'onboarding_completed' => (bool) $user->profile?->onboarding_completed,
            ],
            'next_step' => $this->computeNextStep($user),
        ]);
    }

    private function handleResetVerify(User $user): JsonResponse
    {
        // Short-lived reset token pattern — implemented in step 13
        return ApiResponse::ok(['verified' => true, 'next_step' => 'password.reset']);
    }

    private function computeNextStep(User $user): string
    {
        if (! $user->profile?->onboarding_completed) {
            $step = $user->profile->onboarding_step_completed ?? 0;
            if ($step >= 5) return 'verify.email';
            return 'register.step-' . ($step + 1);
        }
        return 'dashboard';
    }
}
```

### 2. Register routes (with rate limits)

In `routes/api.php` under public routes:

```php
Route::post('/auth/otp/phone/send', [\App\Http\Controllers\Api\V1\AuthController::class, 'sendPhoneOtp'])
    ->middleware('throttle:5,1');  // 5/min/IP

Route::post('/auth/otp/phone/verify', [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyPhoneOtp'])
    ->middleware('throttle:10,1');  // 10/min/IP
```

### 3. Test

```bash
# Send OTP for registration
curl -X POST http://localhost:8000/api/v1/auth/otp/phone/send \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"phone":"9876500020","purpose":"register"}'
# Expect: {"success":true,"data":{"sent":true,...}}

# Check laravel.log for "DEV OTP [phone] for 9876500020: 123456"

# Verify OTP
curl -X POST http://localhost:8000/api/v1/auth/otp/phone/verify \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"phone":"9876500020","otp":"123456","purpose":"register"}'
# Expect: {"success":true,"data":{"verified":true,...}}
```

### 4. Pest tests

Create `tests/Feature/Api/V1/Auth/PhoneOtpTest.php`:

```php
<?php

use App\Models\User;
use function Pest\Laravel\postJson;

it('sends OTP for registration', function () {
    $user = User::factory()->create(['phone' => '9876500030']);

    $response = postJson('/api/v1/auth/otp/phone/send', [
        'phone' => '9876500030',
        'purpose' => 'register',
    ]);

    $response->assertOk()->assertJsonPath('data.sent', true);
});

it('verifies registration OTP and marks phone_verified_at', function () {
    $user = User::factory()->create([
        'phone' => '9876500031',
        'phone_verified_at' => null,
    ]);

    // Trigger OTP send (local env uses 123456)
    postJson('/api/v1/auth/otp/phone/send', ['phone' => '9876500031', 'purpose' => 'register']);

    $response = postJson('/api/v1/auth/otp/phone/verify', [
        'phone' => '9876500031',
        'otp' => '123456',
        'purpose' => 'register',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.verified', true);

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});

it('returns token for login purpose', function () {
    $user = User::factory()->create(['phone' => '9876500032']);
    postJson('/api/v1/auth/otp/phone/send', ['phone' => '9876500032', 'purpose' => 'login']);

    $response = postJson('/api/v1/auth/otp/phone/verify', [
        'phone' => '9876500032',
        'otp' => '123456',
        'purpose' => 'login',
        'device_name' => 'Pixel 8',
    ]);

    $response->assertOk()->assertJsonStructure(['data' => ['token', 'user', 'profile', 'next_step']]);
});

it('rejects invalid OTP', function () {
    User::factory()->create(['phone' => '9876500033']);
    postJson('/api/v1/auth/otp/phone/send', ['phone' => '9876500033', 'purpose' => 'register']);

    $response = postJson('/api/v1/auth/otp/phone/verify', [
        'phone' => '9876500033',
        'otp' => '000000',
        'purpose' => 'register',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'OTP_INVALID');
});
```

## Verification

- [ ] Send OTP returns envelope success
- [ ] Laravel log shows OTP in local env
- [ ] Verify returns envelope with `verified: true`
- [ ] Login purpose returns a Sanctum token
- [ ] Wrong OTP returns 422 with `OTP_INVALID`
- [ ] Rate limit triggers after 5 sends/min (returns 429 with `THROTTLED`)

## Commit

```bash
git add app/Http/Controllers/Api/V1/AuthController.php routes/api.php tests/Feature/Api/V1/Auth/PhoneOtpTest.php
git commit -m "phase-2a wk-02: step-08 phone OTP send + verify endpoints"
```

## Next step
→ [step-09-email-otp-endpoints.md](step-09-email-otp-endpoints.md)
