<?php

namespace App\Support;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Links a phone's FCM push token to a member (the `devices` table NotificationService pushes to).
 * Used by the app API (POST /api/v1/devices) and by the Kudla WebView app through the website
 * session (App\Http\Middleware\RegisterAppDevice).
 */
final class DeviceRegistry
{
    /**
     * Create or refresh the member's row for this token.
     *
     * Anti-hijack: keyed on (user_id, fcm_token). Keying on fcm_token alone would let someone who
     * learned another member's token rewrite user_id on the row and get that member's pushes. A row
     * holding the same token under a DIFFERENT member (a phone handed on, or another member signing
     * in on the same phone) is switched off, so the token reaches only the member signed in now.
     * In a transaction so two members registering the same token at once cannot interleave.
     */
    public static function register(User $user, array $data, ?int $accessTokenId = null): Device
    {
        return DB::transaction(function () use ($user, $data, $accessTokenId) {
            Device::where('fcm_token', $data['fcm_token'])
                ->where('user_id', '!=', $user->id)
                ->update(['is_active' => false]);

            return Device::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'fcm_token' => $data['fcm_token'],
                ],
                [
                    'personal_access_token_id' => $accessTokenId,
                    'platform' => $data['platform'],
                    'device_model' => $data['device_model'] ?? null,
                    'app_version' => $data['app_version'] ?? null,
                    'os_version' => $data['os_version'] ?? null,
                    'locale' => $data['locale'] ?? 'en',
                    'last_seen_at' => now(),
                    'is_active' => true,
                ],
            );
        });
    }

    /** Stop pushing to this token for this member (signed out on that phone). */
    public static function deactivate(User $user, string $token): void
    {
        Device::where('user_id', $user->id)->where('fcm_token', $token)->update(['is_active' => false]);
    }

    /** Logout event: a member signing out in the Kudla app stops that phone's pushes. */
    public static function loggedOut(\Illuminate\Auth\Events\Logout $event): void
    {
        $token = (string) request()->cookies->get(\App\Http\Middleware\RegisterAppDevice::COOKIE, '');
        if ($token !== '' && $event->user instanceof User) {
            self::deactivate($event->user, $token);
        }
    }
}
