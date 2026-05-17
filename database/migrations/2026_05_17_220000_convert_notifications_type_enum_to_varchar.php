<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convert notifications.type from a 5-value ENUM to VARCHAR(50).
 *
 * The original migration (2026_03_31_152119_create_notifications_table)
 * declared the column as
 *
 *     $table->enum('type', ['interest_received', 'interest_accepted',
 *                           'interest_declined', 'profile_view', 'system']);
 *
 * Since then the codebase has added several new notification types
 * (admin_broadcast, plan_changed, document_approved/_rejected,
 * id_proof_approved/_rejected, photo_approved/_rejected, photo_request,
 * membership_expiring, membership_expired, …) without extending the
 * ENUM. Every insert with one of those new values fails with
 *
 *     SQLSTATE[01000]: Warning: 1265 Data truncated for column 'type'
 *
 * (under strict mode this is thrown as a QueryException — the row
 * isn't written; production logs show this firing on photo_request
 * inserts every time a user requests photos).
 *
 * ENUMs are a footgun for an evolving event/notification system —
 * VARCHAR avoids the "remember to extend the ENUM" trap that already
 * bit us. Application-level validation (NotificationService::send
 * still takes a string) prevents arbitrary garbage.
 *
 * Why not just ALTER the ENUM to add the new values? Because the next
 * notification type added in code will silently start failing again.
 * The right fix is to stop using the column type that requires a
 * migration per value.
 *
 * Migration uses a raw ALTER because Laravel's schema builder can't
 * convert ENUM → string in one step on MySQL — it would try to
 * re-define and bump into the existing ENUM constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `notifications` MODIFY `type` VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // Restore the ENUM with all values seen across the codebase as of
        // 2026-05-17. If we ever roll back, we shouldn't lose any types
        // that were valid before the rollback (otherwise rows with newer
        // types couldn't be re-read after the column is restricted).
        $types = [
            'interest_received', 'interest_accepted', 'interest_declined',
            'profile_view', 'system', 'admin_broadcast', 'plan_changed',
            'document_approved', 'document_rejected',
            'id_proof_approved', 'id_proof_rejected',
            'photo_approved', 'photo_rejected', 'photo_request',
            'membership_expiring', 'membership_expired',
        ];
        $list = implode(',', array_map(fn ($t) => "'$t'", $types));
        DB::statement("ALTER TABLE `notifications` MODIFY `type` ENUM($list) NOT NULL");
    }
};
