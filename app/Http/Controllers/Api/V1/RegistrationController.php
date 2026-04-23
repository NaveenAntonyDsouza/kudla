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

/**
 * 5-step mobile registration.
 *
 * Step 1 is public (creates account, returns Sanctum token). Steps 2–5
 * require the token returned from step 1.
 *
 * Business logic lives in App\Services\RegistrationService — this
 * controller is the HTTP adapter: validation (via FormRequest), service
 * dispatch, envelope response.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-06-register-step-1-endpoint.md
 */
class RegistrationController extends BaseApiController
{
    public function __construct(private RegistrationService $registration) {}

    /**
     * Register a new account (step 1 of 5).
     *
     * Creates User + Profile from the validated payload, returns a Sanctum
     * personal access token the client uses to authenticate the next 4 steps.
     *
     * @unauthenticated
     * @group Authentication
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "user": { "id": 42, "name": "Naveen", "email": "naveen@example.com", "phone": "9876543210" },
     *     "profile": { "matri_id": "AM100042", "onboarding_step_completed": 1, "onboarding_completed": false, "is_approved": true },
     *     "token": "5|WGNdkrOzGwpNR...",
     *     "next_step": "register.step-2"
     *   }
     * }
     * @response 422 scenario="duplicate email" {
     *   "success": false,
     *   "error": {
     *     "code": "VALIDATION_FAILED",
     *     "message": "Please check the fields below.",
     *     "fields": { "email": ["This email is already registered. Try logging in instead."] }
     *   }
     * }
     */
    public function step1(RegisterStep1Request $request): JsonResponse
    {
        $result = $this->registration->createFreeAccount(
            $request->validated(),
            $request,
        );

        $user = $result['user'];
        $profile = $result['profile'];

        $token = $user->createToken('register-step-1')->plainTextToken;

        return ApiResponse::created([
            'user' => (new UserResource($user))->resolve(),
            'profile' => [
                'matri_id' => $profile->matri_id,
                'onboarding_step_completed' => 1,
                'onboarding_completed' => false,
                'is_approved' => (bool) $profile->is_approved,
            ],
            'token' => $token,
            'next_step' => 'register.step-2',
        ]);
    }

    /**
     * Register step 2: primary + religious + family info.
     *
     * @authenticated
     * @group Authentication
     */
    public function step2(RegisterStep2Request $request): JsonResponse
    {
        $profile = $this->requireProfile($request);

        $this->registration->updateStep2($profile, $request->validated());

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
     * @authenticated
     * @group Authentication
     */
    public function step3(RegisterStep3Request $request): JsonResponse
    {
        $profile = $this->requireProfile($request);

        $this->registration->updateStep3($profile, $request->validated());

        return ApiResponse::ok([
            'profile' => [
                'matri_id' => $profile->matri_id,
                'onboarding_step_completed' => 3,
            ],
            'next_step' => 'register.step-4',
        ]);
    }

    /**
     * Register step 4: location + contact.
     *
     * @authenticated
     * @group Authentication
     */
    public function step4(RegisterStep4Request $request): JsonResponse
    {
        $profile = $this->requireProfile($request);

        $this->registration->updateStep4($profile, $request->validated());

        return ApiResponse::ok([
            'profile' => [
                'matri_id' => $profile->matri_id,
                'onboarding_step_completed' => 4,
            ],
            'next_step' => 'register.step-5',
        ]);
    }

    /**
     * Register step 5: profile-creator info + finalize. Returns the next
     * screen: 'verify.email', 'verify.phone', or 'complete'.
     *
     * @authenticated
     * @group Authentication
     */
    public function step5(RegisterStep5Request $request): JsonResponse
    {
        $profile = $this->requireProfile($request);

        $nextStep = $this->registration->finalizeStep5($profile, $request->validated());

        return ApiResponse::ok([
            'profile' => [
                'matri_id' => $profile->matri_id,
                'onboarding_step_completed' => 5,
                'onboarding_completed' => (bool) $profile->fresh()->onboarding_completed,
            ],
            'user' => [
                'email_verified_at' => $request->user()->email_verified_at?->toIso8601String(),
                'phone_verified_at' => $request->user()->phone_verified_at?->toIso8601String(),
            ],
            'next_step' => $nextStep,
            'email_verification_enabled' => \App\Models\SiteSetting::getValue('email_verification_enabled', '1') === '1',
            'phone_verification_enabled' => \App\Models\SiteSetting::getValue('phone_verification_enabled', '0') === '1',
        ]);
    }

    /**
     * Pull the authenticated user's Profile or throw 422 if missing.
     * This shouldn't happen in practice — Step 1 creates the Profile
     * alongside the User — but we guard against orphan tokens.
     */
    private function requireProfile($request): \App\Models\Profile
    {
        $profile = $request->user()->profile;

        abort_if(! $profile, 422, 'Profile not found for this account.');

        return $profile;
    }
}
