<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner preference "Don't show profiles with my gotra / bari" (Oct 2026):
 * the same gotra (Brahmins) or bari (Tulu families) counts as brother and
 * sister. Off by default — members choose it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('partner_preferences', 'exclude_same_gotra')) {
            Schema::table('partner_preferences', function (Blueprint $table) {
                $table->boolean('exclude_same_gotra')->default(false)->after('about_partner');
            });
        }
    }

    public function down(): void
    {
        Schema::table('partner_preferences', function (Blueprint $table) {
            $table->dropColumn('exclude_same_gotra');
        });
    }
};
