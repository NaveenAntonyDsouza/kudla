<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Auth\RegisterStep1Request;
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
}
