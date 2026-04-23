<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track when a user last received their weekly match digest email.
     * Used for rate-limiting (don't send again within 5 days — safety against double-runs).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_weekly_match_sent_at')->nullable()->after('reengagement_level');
            $table->index('last_weekly_match_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_weekly_match_sent_at']);
            $table->dropColumn('last_weekly_match_sent_at');
        });
    }
};
