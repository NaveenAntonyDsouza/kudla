# Step 12 — Settings Endpoints

## Goal
7 endpoints: get, visibility, alerts, password, hide, unhide, delete.

## Procedure

### 1. `SettingsController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends BaseApiController
{
    public function __construct(private \App\Services\AuthService $auth) {}

    /** @authenticated @group Settings */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;
        $prefs = $user->notification_preferences ?? [];

        return ApiResponse::ok([
            'visibility' => [
                'show_profile_to' => $profile->show_profile_to ?? 'all',
                'only_same_religion' => (bool) ($profile->only_same_religion ?? false),
                'only_same_denomination' => (bool) ($profile->only_same_denomination ?? false),
                'only_same_mother_tongue' => (bool) ($profile->only_same_mother_tongue ?? false),
                'is_hidden' => (bool) $profile->is_hidden,
            ],
            'alerts' => [
                'email_interest' => $prefs['email_interest'] ?? true,
                'email_accepted' => $prefs['email_accepted'] ?? true,
                'email_declined' => $prefs['email_declined'] ?? false,
                'email_views' => $prefs['email_views'] ?? true,
                'email_promotions' => $prefs['email_promotions'] ?? false,
                'push_interest' => $prefs['push_interest'] ?? true,
                'push_accepted' => $prefs['push_accepted'] ?? true,
                'push_declined' => $prefs['push_declined'] ?? false,
                'push_views' => $prefs['push_views'] ?? false,
                'push_promotions' => $prefs['push_promotions'] ?? false,
            ],
            'auth' => [
                'has_password' => (bool) $user->password,
                'biometric_enrolled' => false,  // client-side state
            ],
            'account' => [
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified' => (bool) $user->email_verified_at,
                'phone_verified' => (bool) $user->phone_verified_at,
            ],
        ]);
    }

    public function visibility(Request $request): JsonResponse
    {
        $data = $request->validate([
            'show_profile_to' => 'sometimes|in:all,premium,matches',
            'only_same_religion' => 'sometimes|boolean',
            'only_same_denomination' => 'sometimes|boolean',
            'only_same_mother_tongue' => 'sometimes|boolean',
        ]);
        $request->user()->profile->update($data);
        return ApiResponse::ok(['updated' => true]);
    }

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
        ]);

        $user = $request->user();
        $prefs = array_merge($user->notification_preferences ?? [], $data);
        $user->update(['notification_preferences' => $prefs]);

        return ApiResponse::ok(['updated' => true]);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:14|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return ApiResponse::error('VALIDATION_FAILED', 'Current password incorrect.',
                fields: ['current_password' => ['Incorrect password.']], status: 422);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        // Revoke all tokens except current
        $currentId = $user->currentAccessToken()->id;
        $count = $user->tokens()->where('id', '!=', $currentId)->count();
        $user->tokens()->where('id', '!=', $currentId)->delete();

        return ApiResponse::ok(['password_changed' => true, 'tokens_revoked_count' => $count]);
    }

    public function hide(Request $request): JsonResponse
    {
        $request->user()->profile->update(['is_hidden' => true]);
        return ApiResponse::ok(['is_hidden' => true]);
    }

    public function unhide(Request $request): JsonResponse
    {
        $request->user()->profile->update(['is_hidden' => false]);
        return ApiResponse::ok(['is_hidden' => false]);
    }

    public function delete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => 'required|string',
            'reason' => 'required|in:found_partner,poor_experience,not_interested,other',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        if (! Hash::check($data['password'], $user->password)) {
            return ApiResponse::error('VALIDATION_FAILED', 'Incorrect password.',
                fields: ['password' => ['Password does not match.']], status: 422);
        }

        // Soft delete
        $user->profile->update([
            'is_active' => false,
            'is_hidden' => true,
            'deletion_reason' => $data['reason'],
            'deletion_feedback' => $data['feedback'] ?? null,
            'deleted_at' => now(),
        ]);

        // Revoke all tokens
        $this->auth->revokeAllTokens($user);

        return ApiResponse::ok(['deleted' => true, 'logged_out' => true]);
    }
}
```

### 2. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\Api\V1\SettingsController::class, 'index']);
    Route::put('/settings/visibility', [\App\Http\Controllers\Api\V1\SettingsController::class, 'visibility']);
    Route::put('/settings/alerts', [\App\Http\Controllers\Api\V1\SettingsController::class, 'alerts']);
    Route::put('/settings/password', [\App\Http\Controllers\Api\V1\SettingsController::class, 'password']);
    Route::post('/settings/hide', [\App\Http\Controllers\Api\V1\SettingsController::class, 'hide']);
    Route::post('/settings/unhide', [\App\Http\Controllers\Api\V1\SettingsController::class, 'unhide']);
    Route::post('/settings/delete', [\App\Http\Controllers\Api\V1\SettingsController::class, 'delete']);
});
```

## Verification
- [ ] GET returns all 4 sections
- [ ] Visibility/alerts accept partial PATCH-style payloads
- [ ] Password change revokes other tokens
- [ ] Delete soft-deletes + logs out everywhere

## Commit
```bash
git commit -am "phase-2a wk-04: step-12 settings endpoints"
```

## Next step
→ [step-13-engagement-public.md](step-13-engagement-public.md)
