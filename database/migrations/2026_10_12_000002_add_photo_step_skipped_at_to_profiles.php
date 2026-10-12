<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers that a member chose "Skip for now" (or "sent on WhatsApp") at the
 * registration photo step, so logging back in shows them that step again only
 * if they never decided (Oct 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('profiles', 'photo_step_skipped_at')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->timestamp('photo_step_skipped_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('profiles', 'photo_step_skipped_at')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->dropColumn('photo_step_skipped_at');
            });
        }
    }
};
