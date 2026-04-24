<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Device (FCM token) registration for push notifications.
 *
 * Flutter client flow:
 *   1. User logs in -> gets Sanctum token.
 *   2. firebase_messaging.getToken() -> FCM token.
 *   3. POST /api/v1/devices {fcm_token, platform, ...}
 *   4. Later: whenever Firebase rotates the token (`onTokenRefresh`),
 *      call POST /api/v1/devices again with the new token. Idempotent
 *      on fcm_token — same row updates.
 *   5. User taps "log out on this device" -> DELETE /api/v1/devices/{id}
 *      (revokes the Sanctum token AND marks device inactive).
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-15-device-registration.md
 *   docs/mobile-app/design/10-push-notifications.md §10.3
 */
class DeviceController extends BaseApiController
{
    /**
     * Register or refresh an FCM device token for the authenticated user.
     *
     * Idempotent on fcm_token — same token re-registered just updates
     * last_seen_at + metadata, returns the same device_id.
     *
     * @authenticated
     * @group Devices
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'required|string|max:255',
            'platform' => 'required|in:android,ios',
            'device_model' => 'nullable|string|max:100',
            'app_version' => 'nullable|string|max:20',
            'os_version' => 'nullable|string|max:20',
            'locale' => 'nullable|string|max:10',
        ]);

        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;

        $device = Device::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            [
                'user_id' => $user->id,
                'personal_access_token_id' => $currentTokenId,
                'platform' => $data['platform'],
                'device_model' => $data['device_model'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'locale' => $data['locale'] ?? 'en',
                'last_seen_at' => now(),
                'is_active' => true,
            ],
        );

        return ApiResponse::created(['device_id' => $device->id]);
    }

    /**
     * Revoke a device (user taps "sign out this device" in settings).
     *
     * Side effect: also revokes the Sanctum token linked to this device
     * so the corresponding /auth/logout isn't needed separately.
     *
     * @authenticated
     * @group Devices
     */
    public function revoke(Request $request, Device $device): JsonResponse
    {
        abort_if($device->user_id !== $request->user()->id, 403, 'This device is not yours.');

        // Revoke the Sanctum token too if we have the link.
        if ($device->personal_access_token_id) {
            \Laravel\Sanctum\PersonalAccessToken::find($device->personal_access_token_id)
                ?->delete();
        }

        $device->update(['is_active' => false]);

        return ApiResponse::ok(['revoked' => true]);
    }
}
