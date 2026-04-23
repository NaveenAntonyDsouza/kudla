<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication helper used by the API layer (Api\V1\AuthController).
 *
 * Design choice — this service is NEW CODE for API use. The existing web
 * LoginController + RegisterController are NOT refactored to call into
 * this service. Rationale:
 *
 *   1. Safety: web is live with real users. Refactoring means risk of
 *      subtle session / guard / remember-me regressions. Not worth it.
 *   2. Web uses session auth + Auth::login(); API uses Sanctum tokens.
 *      The two flows have different side-effect shapes past the "did
 *      the credentials match?" check, so sharing below that level offers
 *      limited DRY value.
 *   3. If we later want web to delegate here, that's a standalone
 *      refactor with its own tests. Not a prereq for the mobile app.
 *
 * Public API:
 *   authenticatePassword(email, password)  -> User|null
 *   authenticatePhoneOtp(phone, otp)       -> User|null    (uses OtpService internally)
 *   authenticateEmailOtp(email, otp)       -> User|null    (uses OtpService internally)
 *   issueToken(User, deviceName, loginType) -> string      (Sanctum plain-text token)
 *   revokeCurrentToken(User)
 *   revokeAllTokens(User)                  -> int          (# of tokens revoked)
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-03-extract-auth-service.md
 */
class AuthService
{
    public function __construct(private OtpService $otp) {}

    /**
     * Verify email + password. Returns the User on success, null otherwise.
     * Does NOT issue a token, log in, or record history — caller does those.
     */
    public function authenticatePassword(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        if (! $user->is_active) {
            return null;  // suspended / deactivated
        }

        return $user;
    }

    /**
     * Verify phone OTP. Caller must have called OtpService::send() first
     * to dispatch the code.
     */
    public function authenticatePhoneOtp(string $phone, string $otp): ?User
    {
        if (! $this->otp->verify($phone, OtpService::CHANNEL_PHONE, $otp)) {
            return null;
        }

        $user = User::where('phone', $phone)->first();

        return ($user && $user->is_active) ? $user : null;
    }

    /**
     * Verify email OTP.
     */
    public function authenticateEmailOtp(string $email, string $otp): ?User
    {
        if (! $this->otp->verify($email, OtpService::CHANNEL_EMAIL, $otp)) {
            return null;
        }

        $user = User::where('email', $email)->first();

        return ($user && $user->is_active) ? $user : null;
    }

    /**
     * Issue a Sanctum personal access token + record all login side effects:
     *   - update last_login_at
     *   - reset reengagement_level = 0 (user is active again)
     *   - create a LoginHistory row
     *
     * Returns the plain-text token (one-time — it's the only time we ever
     * see it in plaintext; after this it lives hashed in personal_access_tokens).
     *
     * $deviceName is stored in personal_access_tokens.name — shows up in any
     * future "active sessions" list.
     *
     * $loginType is the LoginHistory classification:
     *   'password' | 'mobile_otp' | 'email_otp'
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
     * Revoke the token the current request authenticated with. Used by
     * POST /api/v1/auth/logout.
     */
    public function revokeCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }
    }

    /**
     * Revoke ALL tokens for the user. Used when the user changes their
     * password or deletes their account — forces re-auth on every device.
     * Returns the number of tokens that were revoked.
     */
    public function revokeAllTokens(User $user): int
    {
        $count = $user->tokens()->count();
        $user->tokens()->delete();

        return $count;
    }
}
