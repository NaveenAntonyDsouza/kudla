<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks which re-engagement emails a user has received so we don't spam them.
     *
     * `reengagement_level`:
     *   0 = none sent (fresh or recently active)
     *   1 = 7-day reminder sent
     *   2 = 14-day reminder sent
     *   3 = 30-day reminder sent (last email in the cycle)
     *
     * Level resets to 0 when the user next logs in (see LoginController changes in Stage A).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_reengagement_sent_at')->nullable()->after('last_login_at');
            $table->unsignedTinyInteger('reengagement_level')->default(0)->after('last_reengagement_sent_at');

            $table->index('last_reengagement_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_reengagement_sent_at']);
            $table->dropColumn(['last_reengagement_sent_at', 'reengagement_level']);
        });
    }
};
