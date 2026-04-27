<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-device registration for Flutter clients.
 *
 * Each row is a (user, Sanctum token, FCM token) triple. Created when the
 * mobile app calls POST /api/v1/devices after login. Used by
 * NotificationService::sendPush to find which FCM tokens to target for
 * a given user.
 *
 * Why separate from personal_access_tokens?
 *   - A single Sanctum token can run on a single device, but an FCM token
 *     rotates separately (Google rotates it periodically). Decoupling
 *     keeps the rotation trivial.
 *   - We want metadata (platform, device_model, app_version) on devices
 *     for support + analytics + targeted broadcasts.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-15-device-registration.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('personal_access_token_id')
                ->nullable()
                ->constrained('personal_access_tokens')
                ->onDelete('set null');

            $table->string('fcm_token', 255)->unique();
            $table->string('platform', 10);               // 'android' | 'ios'
            $table->string('device_model', 100)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->string('os_version', 20)->nullable();
            $table->string('locale', 10)->default('en');
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
