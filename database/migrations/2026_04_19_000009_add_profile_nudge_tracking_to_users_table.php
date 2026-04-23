<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks profile-completion nudge cadence.
     *
     * nudges_sent_count — lifetime count (cap at 4 → stop nudging)
     * last_nudge_sent_at — rate-limit to 1 nudge per 7 days
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('nudges_sent_count')
                ->default(0)
                ->after('last_weekly_match_sent_at');
            $table->timestamp('last_nudge_sent_at')
                ->nullable()
                ->after('nudges_sent_count');
            $table->index('last_nudge_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_nudge_sent_at']);
            $table->dropColumn(['nudges_sent_count', 'last_nudge_sent_at']);
        });
    }
};
