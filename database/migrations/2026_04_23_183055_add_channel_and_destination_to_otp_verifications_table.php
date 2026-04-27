<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend otp_verifications to support both phone + email OTPs in one table.
 *
 * Before: { id, phone, otp_code, expires_at, verified_at }
 *          — phone-only. Email OTPs live in Laravel session (breaks for
 *          stateless API).
 *
 * After:  { id, phone (nullable), channel, destination, otp_code, ... }
 *          — same table serves both channels. `destination` holds the phone
 *          number OR email. `channel` is 'phone' | 'email'. `phone` column
 *          kept for backwards-compat with existing web queries.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-01-otp-channel-migration.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add new columns, destination nullable during backfill
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('channel', 10)
                ->default('phone')
                ->after('otp_code');

            $table->string('destination', 255)
                ->nullable()
                ->after('channel');
        });

        // Step 2: backfill existing rows
        //   channel     = 'phone'  (existing table was phone-only)
        //   destination = phone value
        DB::table('otp_verifications')
            ->whereNull('destination')
            ->update([
                'channel' => 'phone',
                'destination' => DB::raw('phone'),
            ]);

        // Step 3: tighten destination to NOT NULL + add composite lookup index.
        //         Also make phone nullable — email OTPs won't carry a phone.
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('destination', 255)->nullable(false)->change();
            $table->string('phone', 15)->nullable()->change();

            $table->index(['channel', 'destination'], 'otp_channel_dest_idx');
        });
    }

    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropIndex('otp_channel_dest_idx');

            // Re-tighten phone to NOT NULL. If any email-OTP rows exist,
            // this fails — caller must delete email rows first.
            $table->string('phone', 15)->nullable(false)->change();

            $table->dropColumn(['channel', 'destination']);
        });
    }
};
