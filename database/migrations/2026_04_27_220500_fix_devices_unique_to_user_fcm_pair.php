<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the global unique(fcm_token) on devices with a composite
 * unique(user_id, fcm_token).
 *
 * Why: keying on fcm_token alone let an authenticated attacker who knew
 * another user's FCM token submit it via POST /devices from their own
 * session — Eloquent's updateOrCreate(['fcm_token' => X], ['user_id' => Y, …])
 * semantics on collision UPDATE the existing row, flipping ownership.
 * NotificationService dispatches by user_id so pushes redirect to the
 * attacker, and DELETE /devices/{id} (whose ownership check is `user_id`-
 * based) becomes a Sanctum-token-revoke primitive against the victim.
 *
 * The composite unique stays meaningful: a single user with multiple
 * devices each emit distinct fcm_tokens (Google's tokens are device-
 * specific), so collisions only happen on legitimate re-registration of
 * the same device. Cross-user collisions now error at the DB layer rather
 * than silently rewriting ownership.
 *
 * The DeviceController paired with this migration ALSO defensively
 * deactivates any prior row holding the same fcm_token under a different
 * user_id before insert, so legitimate device hand-offs (sell phone,
 * factory reset, FCM token rotation across users) result in old-row-
 * inactive + new-row-active rather than failing the insert.
 *
 * Pre-deploy data check (paranoia, since the prior unique constraint
 * should have prevented this state, but cheap to verify):
 *
 *     SELECT fcm_token, COUNT(DISTINCT user_id) AS users
 *     FROM devices
 *     GROUP BY fcm_token
 *     HAVING users > 1;
 *
 * Should return zero rows. If it doesn't, manually pick a user_id to
 * keep per fcm_token and DELETE the rest before running this migration —
 * otherwise the new composite unique applies fine but the duplicates
 * stay around as silent stale rows.
 *
 * Reference: Phase 2a security audit, Vuln 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Drop the global unique constraint on fcm_token.
            $table->dropUnique('devices_fcm_token_unique');
        });

        Schema::table('devices', function (Blueprint $table) {
            // Composite unique: same user can't register the same fcm_token
            // twice (idempotent re-register), but two different users can
            // both have rows with the same token (after one is deactivated
            // by the controller — handles the device-hand-off case).
            $table->unique(['user_id', 'fcm_token'], 'devices_user_fcm_unique');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique('devices_user_fcm_unique');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unique('fcm_token', 'devices_fcm_token_unique');
        });
    }
};
