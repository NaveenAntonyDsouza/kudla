<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Account-settings endpoints. 7 routes — one read, six mutations.
 *
 *   GET  /api/v1/settings              full dump (4 sections)
 *   PUT  /api/v1/settings/visibility    profile visibility toggles
 *   PUT  /api/v1/settings/alerts        notification preferences
 *   PUT  /api/v1/settings/password      change password + revoke other tokens
 *   POST /api/v1/settings/hide          hide profile from search
 *   POST /api/v1/settings/unhide        unhide
 *   POST /api/v1/settings/delete        soft-delete account
 *
 * Mutations accept partial PATCH-style payloads (`sometimes|...` rules)
 * so Flutter can update one toggle at a time without re-sending the
 * whole settings tree.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-12-settings.md
 */
class SettingsController extends BaseApiController
{
    /** Reasons accepted by the delete-account endpoint. */
    private const DELETE_REASONS = [
        'found_partner',
        'poor_experience',
        'not_interested',
        'other',
    ];

    public function __construct(private AuthService $auth) {}

    /* ==================================================================
     |  GET /settings
     | ================================================================== */

    /**
     * Full settings dump — visibility, alerts, auth flags, account
     * status. Flutter renders the settings screen entirely from this
     * single payload.
     *
     * @authenticated
     *
     * @group Settings
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "visibility": {"show_profile_to": "all", "only_same_religion": false, "only_same_denomination": false, "only_same_mother_tongue": false, "is_hidden": false},
     *     "alerts": {"email_interest": true, "push_interest": true, "quiet_hours_start": null, "quiet_hours_end": null, ...},
     *     "auth": {"has_password": true},
     *     "account": {"email": "...", "phone": "...", "email_verified": true, "phone_verified": true}
     *   }
     * }
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;
        if (! $profile) {
            return $this->profileRequired();
        }

        $prefs = $user->notification_preferences ?? [];

        return ApiResponse::ok([
            'visibility' => [
                'show_profile_to' => (string) ($profile->show_profile_to ?? 'all'),
                'only_same_religion' => (bool) ($profile->only_same_religion ?? false),
                'only_same_denomination' => (bool) ($profile->only_same_denomination ?? false),
                'only_same_mother_tongue' => (bool) ($profile->only_same_mother_tongue ?? false),
                'is_hidden' => (bool) $profile->is_hidden,
            ],
            'alerts' => $this->alertsShape($prefs),
            'auth' => [
                'has_password' => ! empty($user->password),
            ],
            'account' => [
                'email' => (string) ($user->email ?? ''),
                'phone' => (string) ($user->phone ?? ''),
                'email_verified' => $user->email_verified_at !== null,
                'phone_verified' => $user->phone_verified_at !== null,
            ],
        ]);
    }

    /* ==================================================================
     |  PUT /settings/visibility
     | ================================================================== */

    /**
     * Update profile-visibility toggles. PATCH-style — send only the
     * keys you want to change.
     *
     * @authenticated
     *
     * @group Settings
     *
     * @bodyParam show_profile_to string Optional. all|premium|matches.
     * @bodyParam only_same_religion boolean Optional.
     * @bodyParam only_same_denomination boolean Optional.
     * @bodyParam only_same_mother_tongue boolean Optional.
     *
     * @response 200 scenario="success" {"success": true, "data": {"updated": true}}
     */
    public function visibility(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->profile) {
            return $this->profileRequired();
        }

        $data = $request->validate([
            'show_profile_to' => 'sometimes|in:all,premium,matches',
            'only_same_religion' => 'sometimes|boolean',
            'only_same_denomination' => 'sometimes|boolean',
            'only_same_mother_tongue' => 'sometimes|boolean',
        ]);

        if (! empty($data)) {
            $user->profile->update($data);
        }

        return ApiResponse::ok(['updated' => true]);
    }

    /* ==================================================================
     |  PUT /settings/alerts
     | ================================================================== */

    /**
     * Update notification preferences. PATCH-style — merges with
     * existing prefs so unchanged keys retain their values.
     *
     * Quiet-hours window is optional; both start and end must be
     * present together (or both null/absent). Used by
     * NotificationService::sendPush to skip non-priority pushes
     * during the user's chosen quiet hours.
     *
     * @authenticated
     *
     * @group Settings
     *
     * @bodyParam email_interest boolean Optional.
     * @bodyParam email_accepted boolean Optional.
     * @bodyParam email_declined boolean Optional.
     * @bodyParam email_views boolean Optional.
     * @bodyParam email_promotions boolean Optional.
     * @bodyParam push_interest boolean Optional.
     * @bodyParam push_accepted boolean Optional.
     * @bodyParam push_declined boolean Optional.
     * @bodyParam push_views boolean Optional.
     * @bodyParam push_promotions boolean Optional.
     * @bodyParam quiet_hours_start string Optional. HH:MM 24h. Pair with quiet_hours_end.
     * @bodyParam quiet_hours_end string Optional. HH:MM 24h.
     *
     * @response 200 scenario="success" {"success": true, "data": {"updated": true}}
     */
    public function alerts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email_interest' => 'sometimes|boolean',
            'email_accepted' => 'sometimes|boolean',
            'email_declined' => 'sometimes|boolean',
            'email_views' => 'sometimes|boolean',
            'email_promotions' => 'sometimes|boolean',
            'push_interest' => 'sometimes|boolean',
            'push_accepted' => 'sometimes|boolean',
            'push_declined' => 'sometimes|boolean',
            'push_views' => 'sometimes|boolean',
            'push_promotions' => 'sometimes|boolean',
            // Empty string clears the window (null sentinel for the JSON column).
            'quiet_hours_start' => ['sometimes', 'nullable', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/'],
            'quiet_hours_end' => ['sometimes', 'nullable', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/'],
        ]);

        $user = $request->user();
        $prefs = $user->notification_preferences ?? [];

        // Boolean toggles get coerced (validator's "sometimes|boolean" leaves
        // values as the raw input — could be int, string, etc.). Quiet-hours
        // strings stay as-is.
        $stringKeys = ['quiet_hours_start', 'quiet_hours_end'];
        foreach ($data as $key => $value) {
            if (in_array($key, $stringKeys, true)) {
                // Empty string clears the window — store as null.
                $prefs[$key] = $value === '' ? null : $value;
            } else {
                $prefs[$key] = $value === null ? null : (bool) $value;
            }
        }

        $user->update(['notification_preferences' => $prefs]);

        return ApiResponse::ok(['updated' => true]);
    }

    /* ==================================================================
     |  PUT /settings/password
     | ================================================================== */

    /**
     * Change password + revoke every OTHER active session/token.
     * Current session stays alive so Flutter doesn't get logged out
     * mid-update.
     *
     * @authenticated
     *
     * @group Settings
     *
     * @bodyParam current_password string required Current password.
     * @bodyParam new_password string required Min 6, max 14, must match new_password_confirmation.
     * @bodyParam new_password_confirmation string required Confirmation.
     *
     * @response 200 scenario="success" {"success": true, "data": {"password_changed": true, "tokens_revoked_count": 2}}
     * @response 422 scenario="wrong-current" {"success": false, "error": {"code": "VALIDATION_FAILED", "fields": {"current_password": ["Incorrect password."]}}}
     */
    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:14|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                'Current password is incorrect.',
                ['current_password' => ['Incorrect password.']],
                422,
            );
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        $count = $this->revokeOtherTokens($user);

        return ApiResponse::ok([
            'password_changed' => true,
            'tokens_revoked_count' => $count,
        ]);
    }

    /* ==================================================================
     |  POST /settings/hide  +  /settings/unhide
     | ================================================================== */

    /**
     * Hide profile from search + recommendations.
     *
     * @authenticated
     *
     * @group Settings
     *
     * @response 200 scenario="success" {"success": true, "data": {"is_hidden": true}}
     */
    public function hide(Request $request): JsonResponse
    {
        if (! $request->user()->profile) {
            return $this->profileRequired();
        }

        $request->user()->profile->update(['is_hidden' => true]);

        return ApiResponse::ok(['is_hidden' => true]);
    }

    /**
     * Unhide profile (reverse of hide).
     *
     * @authenticated
     *
     * @group Settings
     *
     * @response 200 scenario="success" {"success": true, "data": {"is_hidden": false}}
     */
    public function unhide(Request $request): JsonResponse
    {
        if (! $request->user()->profile) {
            return $this->profileRequired();
        }

        $request->user()->profile->update(['is_hidden' => false]);

        return ApiResponse::ok(['is_hidden' => false]);
    }

    /* ==================================================================
     |  POST /settings/delete
     | ================================================================== */

    /**
     * Soft-delete the account. Sets is_active=false + is_hidden=true,
     * stores the deletion reason (with optional free-form feedback
     * folded in for "other"), then SoftDeletes the profile (auto-sets
     * deleted_at via the Profile model trait), and revokes every
     * Sanctum token so the current session is dropped too.
     *
     * Reactivation is admin-only — buyer's support team handles it.
     *
     * Schema note: there is NO `deletion_feedback` column. Optional
     * text is concatenated into `deletion_reason` like the web flow
     * already does ("Other: <text>").
     *
     * @authenticated
     *
     * @group Settings
     *
     * @bodyParam password string required Password confirmation — defends against accidental delete.
     * @bodyParam reason string required One of: found_partner, poor_experience, not_interested, other.
     * @bodyParam feedback string Optional. Free-form text (max 2000) — folded into deletion_reason when reason=other.
     *
     * @response 200 scenario="success" {"success": true, "data": {"deleted": true, "logged_out": true}}
     * @response 422 scenario="wrong-password" {"success": false, "error": {"code": "VALIDATION_FAILED", "fields": {"password": ["Password does not match."]}}}
     */
    public function delete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => 'required|string',
            'reason' => 'required|string|in:'.implode(',', self::DELETE_REASONS),
            'feedback' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        if (! $user->profile) {
            return $this->profileRequired();
        }

        if (! Hash::check($data['password'], (string) $user->password)) {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                'Password is incorrect.',
                ['password' => ['Password does not match.']],
                422,
            );
        }

        // Stash optional feedback into deletion_reason (matches web's
        // existing format). The `deletion_feedback` column doesn't
        // exist on the schema.
        $reasonText = $data['reason'] === 'other' && ! empty($data['feedback'])
            ? 'other: '.$data['feedback']
            : $data['reason'];

        $user->profile->update([
            'is_active' => false,
            'is_hidden' => true,
            'deletion_reason' => $reasonText,
        ]);
        // Soft-delete via the SoftDeletes trait — auto-sets deleted_at.
        $user->profile->delete();

        $this->auth->revokeAllTokens($user);

        return ApiResponse::ok([
            'deleted' => true,
            'logged_out' => true,
        ]);
    }

    /* ==================================================================
     |  Test seams
     | ================================================================== */

    /**
     * Revoke every Sanctum token for the user EXCEPT the one in the
     * current request. Returns the count revoked.
     *
     * Protected so tests can override and skip the
     * personal_access_tokens-table dependency.
     */
    protected function revokeOtherTokens(User $user): int
    {
        $currentId = $user->currentAccessToken()?->id;
        if ($currentId === null) {
            return 0;
        }

        $count = $user->tokens()->where('id', '!=', $currentId)->count();
        $user->tokens()->where('id', '!=', $currentId)->delete();

        return $count;
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    /**
     * Default-aware shape of the alerts section. Pulls each key from
     * prefs with a sensible default — defaulting to true for
     * informational alerts, false for promotional / engagement alerts
     * where users typically want to opt-IN.
     */
    private function alertsShape(array $prefs): array
    {
        return [
            'email_interest' => (bool) ($prefs['email_interest'] ?? true),
            'email_accepted' => (bool) ($prefs['email_accepted'] ?? true),
            'email_declined' => (bool) ($prefs['email_declined'] ?? false),
            'email_views' => (bool) ($prefs['email_views'] ?? true),
            'email_promotions' => (bool) ($prefs['email_promotions'] ?? false),
            'push_interest' => (bool) ($prefs['push_interest'] ?? true),
            'push_accepted' => (bool) ($prefs['push_accepted'] ?? true),
            'push_declined' => (bool) ($prefs['push_declined'] ?? false),
            'push_views' => (bool) ($prefs['push_views'] ?? false),
            'push_promotions' => (bool) ($prefs['push_promotions'] ?? false),
            'quiet_hours_start' => $prefs['quiet_hours_start'] ?? null,
            'quiet_hours_end' => $prefs['quiet_hours_end'] ?? null,
        ];
    }

    private function profileRequired(): JsonResponse
    {
        return ApiResponse::error(
            'PROFILE_REQUIRED',
            'Complete registration before changing settings.',
            null,
            422,
        );
    }
}
