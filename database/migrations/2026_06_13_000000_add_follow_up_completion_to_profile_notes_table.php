<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let staff mark a scheduled follow-up as DONE instead of it leaking into the
 * Overdue list forever.
 *
 * We DON'T null out follow_up_date when a follow-up is completed — that date is
 * part of the immutable interaction log shown on the member's profile (it
 * records what was scheduled and when). Instead we stamp a separate
 * follow_up_completed_at (+ who completed it), and every "pending follow-up"
 * query filters on `follow_up_completed_at IS NULL`. This keeps the full
 * history, records who actioned it, and makes "Undo" trivial (null the stamp).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_notes', function (Blueprint $table) {
            $table->timestamp('follow_up_completed_at')->nullable()->after('follow_up_date');
            // nullOnDelete (not cascade like admin_user_id): deleting the staff
            // member who completed a follow-up must not delete the note itself.
            $table->foreignId('follow_up_completed_by')->nullable()->after('follow_up_completed_at')
                ->constrained('users')->nullOnDelete();

            // The report + badge + dashboard widget all filter pending follow-ups
            // by (follow_up_date, follow_up_completed_at), so index the new col.
            $table->index('follow_up_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('profile_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('follow_up_completed_by');
            $table->dropIndex(['follow_up_completed_at']);
            $table->dropColumn('follow_up_completed_at');
        });
    }
};
