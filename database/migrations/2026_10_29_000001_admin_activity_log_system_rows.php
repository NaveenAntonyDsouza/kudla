<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin audit (29 Oct 2026): every action was filed under the main admin
 * account, scheduled jobs included, and deleting a staff account deleted
 * their whole history with it. Now:
 * - admin_user_id may be empty = done by the site itself ("System");
 * - rows recorded without an IP (jobs, server scripts — never the admin
 *   panel, which always has one) move to System;
 * - deleting a user keeps their rows (null instead of cascade).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_activity_log')) {
            return;
        }

        Schema::table('admin_activity_log', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
        });
        Schema::table('admin_activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable()->change();
            $table->foreign('admin_user_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('admin_activity_log')->whereNull('ip_address')->update(['admin_user_id' => null]);
    }

    public function down(): void
    {
        // rows moved to System stay there; the column stays nullable
        // (making it NOT NULL again would fail on those rows)
    }
};
