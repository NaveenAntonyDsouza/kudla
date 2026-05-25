<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track which device/channel a member registered from.
 *
 * Values (App\Support\DeviceDetector constants):
 *   Desktop | Mobile | Tablet  — web sign-ups, classified from the
 *                                User-Agent at registration time
 *   App                        — native mobile app (via the /api/v1
 *                                registration endpoint)
 *   Admin                      — created by staff via "Register on Behalf"
 *
 * Nullable: the ~190 profiles that existed before this column show
 * NULL (rendered as "—" in admin). Only registrations from this
 * point forward are classified — we can't retroactively know the
 * device of past sign-ups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('registration_source', 20)->nullable()->after('created_by_staff_id');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('registration_source');
        });
    }
};
