<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Details checked on a call" (6 Nov 2026). Staff go through a member's details with
 * them on the phone and correct what is wrong. When nothing needed changing, nothing
 * showed that the profile had been checked. These hold the last check (when, which
 * staff member); every check is also in the Activity Log as member_details_checked.
 * Staff-only: never shown to members (hidden on the Profile model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'details_checked_at')) {
                $table->timestamp('details_checked_at')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'details_checked_by')) {
                $table->unsignedBigInteger('details_checked_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            foreach (['details_checked_at', 'details_checked_by'] as $column) {
                if (Schema::hasColumn('profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
