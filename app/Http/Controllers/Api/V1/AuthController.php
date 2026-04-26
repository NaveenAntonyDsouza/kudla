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
     *
     * @bodyParam phone string required 10-digit phone number. Example: 9876543210
     * @bodyParam purpose string required One of: register | login | reset.
     *
     * @response 200 scenario="success" {"success": true, "data": {"sent": true, "expires_in": 300}}
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "Please check the fields below.", "fields": {"phone": ["The phone field is required."]}}}
     * @response 429 scenario="cooldown" {"success": false, "error": {"code": "OTP_COOLDOWN", "message": "Please wait before requesting another code."}}
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
     *
     * @bodyParam phone string required 10-digit phone number.
     * @bodyParam otp string required 6-digit OTP code.
     * @bodyParam purpose string required One of: register | login | reset.
     * @bodyParam device_name string Optional device label (max 60 chars). Used on login token.
     *
     * @response 200 scenario="login-success" {"success": true, "data": {"token": "5|abc...", "user": {"id": 42}, "next_step": "complete"}}
     * @response 200 scenario="register-verify-success" {"success": true, "data": {"verified": true, "next_step": "register.step-2"}}
     * @response 422 scenario="otp-invalid" {"success": false, "error": {"code": "OTP_INVALID", "message": "Invalid or expired code."}}
     * @response 422 scenario="otp-expired" {"success": false, "error": {"code": "OTP_EXPIRED", "message": "Code has expired. Send a new one."}}
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
     *
     * @bodyParam email string required Valid email address.
     * @bodyParam purpose string required One of: register | login | reset.
     *
     * @response 200 scenario="success" {"success": true, "data": {"sent": true, "expires_in": 300}}
     * @response 422 scenario="login-disabled" {"success": false, "error": {"code": "OTP_INVALID", "message": "Email OTP login is not enabled."}}
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"email": ["..."]}}}
     * @response 429 scenario="cooldown" {"success": false, "error": {"code": "OTP_COOLDOWN", "message": "Please wait before requesting another code."}}
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
     *
     * @bodyParam email string required Email address that received the OTP.
     * @bodyParam otp string required 6-digit OTP code.
     * @bodyParam purpose string required One of: register | login | reset.
     * @bodyParam device_name string Optional device label (max 60 chars). Used on login token.
     *
     * @response 200 scenario="login-success" {"success": true, "data": {"token": "5|abc...", "user": {"id": 42}, "next_step": "complete"}}
     * @response 200 scenario="register-verify-success" {"success": true, "data": {"verified": true, "next_step": "register.step-2"}}
     * @response 422 scenario="otp-invalid" {"success": false, "error": {"code": "OTP_INVALID", "message": "Invalid or expired code."}}
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
     *
     * @bodyParam email string required Registered email address.
     * @bodyParam password string required Account password.
     * @bodyParam device_name string Optional device label (max 60 chars). Defaults to "mobile".
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "token": "5|abc123...",
     *     "user": {"id": 42, "name": "Naveen", "email": "naveen@example.com"},
     *     "profile": {"matri_id": "AM100042", "onboarding_completed": true},
     *     "membership": {"is_premium": false},
     *     "next_step": "home"
     *   }
     * }
     * @response 401 scenario="bad-credentials" {"success": false, "error": {"code": "UNAUTHENTICATED", "message": "Invalid email or password."}}
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"email": ["..."]}}}
     * @response 429 scenario="throttled" {"success": false, "error": {"code": "THROTTLED", "message": "Too many attempts. Try again later."}}
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
     |  Forgot + reset password (Laravel Password broker)
     | ------------------------------------------------------------------ */

    /**
     * Send a password reset email via Laravel's Password broker.
     *
     * Always returns envelope success, regardless of whether the email
     * exists — anti-enumeration. If an account exists, a reset link is
     * dispatched to the user's email. The link points to APP_URL/reset-password/{token}
     * which the Flutter App Links intent filter (step-17 of Flutter plan)
     * intercepts to open the reset screen in-app.
     *
     * @unauthenticated
     * @group Authentication
     *
     * @bodyParam email string required Email address to send the reset link to. Always returns success even when the email is unknown (anti-enumeration).
     *
     * @response 200 scenario="success" {"success": true, "data": {"sent": true, "message": "If that email is registered, a password reset link has been sent."}}
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"email": ["The email field is required."]}}}
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        // Password::sendResetLink is silent on unknown emails — exactly
        // what we want. It returns a status enum; we don't leak it.
        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $data['email']]);

        return ApiResponse::ok([
            'sent' => true,
            'message' => 'If that email is registered, a password reset link has been sent.',
        ]);
    }

    /**
     * Complete the password reset using the token from the reset email.
     *
     * Side effect: on success, all Sanctum tokens for the user are revoked
     * (force re-login on every device for security).
     *
     * @unauthenticated
     * @group Authentication
     *
     * @bodyParam email string required Email the reset link was sent to.
     * @bodyParam token string required Reset token from the URL Laravel emailed.
     * @bodyParam password string required New password (6-14 chars).
     * @bodyParam password_confirmation string required Must match password.
     *
     * @response 200 scenario="success" {"success": true, "data": {"reset": true, "message": "Password updated. Please sign in again."}}
     * @response 422 scenario="invalid-token" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"token": ["This reset token is invalid or has expired."]}}}
     * @response 422 scenario="weak-password" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"password": ["The password must be at least 6 characters."]}}}
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

        $status = \Illuminate\Support\Facades\Password::reset(
            $data,
            function ($user, $password) {
                $user->update([
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                ]);
                // Revoke every Sanctum token — all devices must re-login.
                $this->auth->revokeAllTokens($user);
            },
        );

        if ($status !== \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return ApiResponse::error(
                code: 'VALIDATION_FAILED',
                message: 'The reset link is invalid or has expired.',
                fields: ['token' => [__($status)]],
                status: 422,
            );
        }

        return ApiResponse::ok(['reset' => true]);
    }

    /* ------------------------------------------------------------------
     |  Session lifecycle (/me + /logout)
     | ------------------------------------------------------------------ */

    /**
     * Return the currently-authenticated user with profile, membership, and
     * next-step hint. Flutter calls this on every app launch to validate
     * the stored token — a 401 here tells the client to drop the token and
     * route to the login screen.
     *
     * @authenticated
     * @group Authentication
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "user": {"id": 42, "name": "Naveen", "email": "naveen@example.com", "phone": "9876543210"},
     *     "profile": {"matri_id": "AM100042", "onboarding_completed": true, "is_approved": true, "is_active": true},
     *     "membership": {"is_premium": false, "plan_id": null, "expires_at": null},
     *     "next_step": "home"
     *   }
     * }
     * @response 401 scenario="invalid-token" {"success": false, "error": {"code": "UNAUTHENTICATED", "message": "You must log in to access this resource."}}
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::ok([
            'user' => (new UserResource($user))->resolve(),
            'profile' => $this->profileSummary($user),
            'membership' => $this->membershipSummary($user),
            'next_step' => $this->nextStepForUser($user),
        ]);
    }

    /**
     * Revoke the token the current request authenticated with. Only this
     * device's token is revoked — other devices stay signed in.
     *
     * @authenticated
     * @group Authentication
     *
     * @response 200 scenario="success" {"success": true, "data": {"logged_out": true}}
     * @response 401 scenario="invalid-token" {"success": false, "error": {"code": "UNAUTHENTICATED", "message": "You must log in to access this resource."}}
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->revokeCurrentToken($request->user());

        return ApiResponse::ok(['logged_out' => true]);
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
