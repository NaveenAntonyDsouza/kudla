<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authentication endpoints for the mobile API.
 *
 * Covers:
 *   - OTP send / verify for phone + email (3 purposes: register|login|reset)
 *   - Login via password / phone-OTP / email-OTP
 *   - Forgot + reset password (Laravel Password broker)
 *   - /auth/me + /auth/logout
 *
 * This controller delegates all business logic to:
 *   - App\Services\OtpService        (send + verify OTPs)
 *   - App\Services\AuthService       (authenticate + issue/revoke tokens)
 *   - App\Services\RegistrationService (next verification step lookup)
 *
 * Design reference:
 *   docs/mobile-app/design/02-auth-api.md
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-08-phone-otp-endpoints.md
 */
class AuthController extends BaseApiController
{
    public function __construct(
        private OtpService $otp,
        private AuthService $auth,
        private RegistrationService $registration,
    ) {}

    /* ------------------------------------------------------------------
     |  Phone OTP
     | ------------------------------------------------------------------ */

    /**
     * Dispatch a phone OTP.
     *
     * For purpose=login|reset, we silently short-circuit if the phone is
     * not in our DB — we respond success either way so attackers can't
     * enumerate registered numbers.
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

        // Feature gate for purpose=login (admin can disable mobile OTP login).
        if ($data['purpose'] === 'login'
            && SiteSetting::getValue('mobile_otp_login_enabled', '1') !== '1') {
            return ApiResponse::error(
                code: 'UNAUTHORIZED',
                message: 'Mobile OTP login is currently disabled.',
                status: 403,
            );
        }

        // Silent short-circuit for login/reset on unknown phone numbers.
        if (in_array($data['purpose'], ['login', 'reset'], true)
            && ! User::where('phone', $data['phone'])->exists()) {
            return $this->sendOk();
        }

        $this->otp->send($data['phone'], OtpService::CHANNEL_PHONE);

        return $this->sendOk();
    }

    /**
     * Verify a phone OTP. Behavior branches on purpose:
     *   register  -> set user.phone_verified_at, return next onboarding step
     *   login     -> issue Sanctum token + user/profile/membership snapshot
     *   reset     -> placeholder ok response; actual reset handled by
     *                /auth/password/reset (step-13) using Laravel broker
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
            return ApiResponse::error(
                code: 'OTP_INVALID',
                message: 'Invalid or expired OTP.',
                status: 422,
            );
        }

        $user = User::where('phone', $data['phone'])->first();
        if (! $user) {
            return ApiResponse::error(
                code: 'NOT_FOUND',
                message: 'No account found with this phone number.',
                status: 404,
            );
        }

        return match ($data['purpose']) {
            'register' => $this->handleRegisterVerify($user, 'phone'),
            'login' => $this->handleLoginVerify($user, $data['device_name'] ?? 'Mobile', 'mobile_otp'),
            'reset' => $this->handleResetVerify(),
        };
    }

    /* ------------------------------------------------------------------
     |  Email OTP
     | ------------------------------------------------------------------ */

    /**
     * Dispatch an email OTP. Mirror of sendPhoneOtp — same 3 purposes,
     * but feature-flag for login purpose defaults to DISABLED (admin must
     * explicitly enable email OTP login via site_settings).
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

        if ($data['purpose'] === 'login'
            && SiteSetting::getValue('email_otp_login_enabled', '0') !== '1') {
            return ApiResponse::error(
                code: 'UNAUTHORIZED',
                message: 'Email OTP login is currently disabled.',
                status: 403,
            );
        }

        // Silent short-circuit for login/reset on unknown emails (anti-enumeration).
        if (in_array($data['purpose'], ['login', 'reset'], true)
            && ! User::where('email', $data['email'])->exists()) {
            return $this->sendOk();
        }

        $this->otp->send($data['email'], OtpService::CHANNEL_EMAIL);

        return $this->sendOk();
    }

    /**
     * Verify an email OTP. Dispatches to the same handleX handlers used
     * by phone OTP (single source of truth for the 3 purpose branches).
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
            return ApiResponse::error(
                code: 'OTP_INVALID',
                message: 'Invalid or expired OTP.',
                status: 422,
            );
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return ApiResponse::error(
                code: 'NOT_FOUND',
                message: 'No account found with this email.',
                status: 404,
            );
        }

        return match ($data['purpose']) {
            'register' => $this->handleRegisterVerify($user, 'email'),
            'login' => $this->handleLoginVerify($user, $data['device_name'] ?? 'Mobile', 'email_otp'),
            'reset' => $this->handleResetVerify(),
        };
    }

    /* ------------------------------------------------------------------
     |  Password login
     | ------------------------------------------------------------------ */

    /**
     * Log in with email + password. Primary login flow for existing users.
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
            // Generic UNAUTHENTICATED — don't leak whether the email exists.
            return ApiResponse::error(
                code: 'UNAUTHENTICATED',
                message: 'Invalid email or password.',
                status: 401,
            );
        }

        return $this->handleLoginVerify($user, $data['device_name'] ?? 'Mobile', 'password');
    }

    /* ------------------------------------------------------------------
     |  Shared handlers (used by both phone + email OTP verify + password login)
     | ------------------------------------------------------------------ */

    /**
     * Mark the phone or email as verified + compute next onboarding step.
     * Called from both verifyPhoneOtp and verifyEmailOtp (future step-09).
     */
    private function handleRegisterVerify(User $user, string $channel): JsonResponse
    {
        $field = $channel === 'phone' ? 'phone_verified_at' : 'email_verified_at';
        $user->update([$field => now()]);

        $nextStep = $this->registration->nextVerificationStep($user->fresh());

        return ApiResponse::ok([
            'verified' => true,
            'user' => [
                'phone_verified_at' => $user->fresh()->phone_verified_at?->toIso8601String(),
                'email_verified_at' => $user->fresh()->email_verified_at?->toIso8601String(),
            ],
            'onboarding_completed' => (bool) $user->fresh()->profile?->onboarding_completed,
            'next_step' => $nextStep === 'complete' ? 'dashboard' : $nextStep,
        ]);
    }

    /**
     * Issue a Sanctum token for the user. Used by:
     *   - POST /auth/login/password          (step-10, AuthController::loginPassword)
     *   - POST /auth/otp/phone/verify with purpose=login  (here)
     *   - POST /auth/otp/email/verify with purpose=login  (step-09)
     */
    private function handleLoginVerify(User $user, string $deviceName, string $loginType): JsonResponse
    {
        if (! $user->is_active) {
            return ApiResponse::error(
                code: 'PROFILE_SUSPENDED',
                message: 'This account is currently inactive. Contact support.',
                status: 403,
            );
        }

        $token = $this->auth->issueToken($user, $deviceName, $loginType);

        return ApiResponse::ok([
            'token' => $token,
            'user' => (new UserResource($user->fresh()))->resolve(),
            'profile' => $this->profileSummary($user),
            'membership' => $this->membershipSummary($user),
            'next_step' => $this->nextStepForUser($user),
        ]);
    }

    /**
     * Placeholder for purpose=reset verify. Actual reset happens via
     * /auth/password/reset in step-13 (uses Laravel's Password broker
     * + signed token). This just ack's that the OTP was valid so the
     * client can transition UI.
     */
    private function handleResetVerify(): JsonResponse
    {
        return ApiResponse::ok([
            'verified' => true,
            'next_step' => 'password.reset',
        ]);
    }

    /* ------------------------------------------------------------------
     |  Envelope helpers
     | ------------------------------------------------------------------ */

    /** Lightweight Profile snapshot for login/me responses. */
    private function profileSummary(User $user): array
    {
        $profile = $user->profile;

        return [
            'matri_id' => $profile?->matri_id,
            'onboarding_step_completed' => (int) ($profile?->onboarding_step_completed ?? 0),
            'onboarding_completed' => (bool) $profile?->onboarding_completed,
            'profile_completion_pct' => (int) ($profile?->profile_completion_pct ?? 0),
        ];
    }

    /** Lightweight membership snapshot. */
    private function membershipSummary(User $user): array
    {
        $active = $user->activeMembership();

        return [
            'plan' => $active?->plan?->title ?? 'Free',
            'ends_at' => $active?->ends_at?->toIso8601String(),
            'is_premium' => $user->isPremium(),
        ];
    }

    /** Where should Flutter route this user next? */
    private function nextStepForUser(User $user): string
    {
        if (! $user->profile?->onboarding_completed) {
            $step = (int) ($user->profile?->onboarding_step_completed ?? 0);
            if ($step < 5) {
                return 'register.step-' . ($step + 1);
            }
            // All 5 steps done — check verification gates
            return $this->registration->nextVerificationStep($user);
        }

        return 'dashboard';
    }

    /** Standard "OTP dispatched" response — used by send + silent-short-circuit paths. */
    private function sendOk(): JsonResponse
    {
        return ApiResponse::ok([
            'sent' => true,
            'expires_in_seconds' => config('matrimony.otp_expiry_minutes', 10) * 60,
            'cooldown_seconds' => config('matrimony.otp_cooldown_seconds', 30),
        ]);
    }
}
