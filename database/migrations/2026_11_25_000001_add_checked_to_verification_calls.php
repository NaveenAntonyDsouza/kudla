<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Needs a check (owner, 25 Nov 2026): calls that ended as "details did not match", "not
 * looking any more" or "wrong number" wait there for a manager, who presses Checked once they
 * have dealt with it (hidden a settled member's profile, emailed for the right number…).
 * Checked takes the call off the list; when and by whom is kept here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('verification_calls') && ! Schema::hasColumn('verification_calls', 'checked_at')) {
            Schema::table('verification_calls', function (Blueprint $table) {
                $table->timestamp('checked_at')->nullable()->after('done_by');
                $table->foreignId('checked_by')->nullable()->after('checked_at')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('verification_calls') && Schema::hasColumn('verification_calls', 'checked_at')) {
            Schema::table('verification_calls', function (Blueprint $table) {
                $table->dropConstrainedForeignId('checked_by');
                $table->dropColumn('checked_at');
            });
        }
    }
};
