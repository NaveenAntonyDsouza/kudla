<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Delete Account" only hid the profile, although the privacy policy promises
 * the personal data is deleted or anonymised within 90 days (Oct 2026 audit:
 * 27 kudla members since June). account_deleted_at = when the member asked;
 * data_erased_at = when members:erase-deleted removed their details.
 * Existing deletions are backfilled from when the profile last changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'account_deleted_at')) {
                $table->timestamp('account_deleted_at')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'data_erased_at')) {
                $table->timestamp('data_erased_at')->nullable();
            }
        });

        DB::table('profiles')
            ->whereNotNull('deletion_reason')
            ->where('is_active', false)
            ->whereNull('account_deleted_at')
            ->update(['account_deleted_at' => DB::raw('COALESCE(deleted_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            foreach (['account_deleted_at', 'data_erased_at'] as $col) {
                if (Schema::hasColumn('profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
