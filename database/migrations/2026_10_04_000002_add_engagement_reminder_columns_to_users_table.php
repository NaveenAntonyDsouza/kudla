<?php

use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - users.last_interest_reminder_at / last_payment_reminder_at: at most one
 *   "interests waiting for your reply" and one "complete your payment" email
 *   per member per week.
 * - The interest-received email gains Accept / Decline buttons and a
 *   one-line summary of the sender; replaced only where the body is still
 *   the original default, so an admin's own wording is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_interest_reminder_at')) {
                $table->timestamp('last_interest_reminder_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_payment_reminder_at')) {
                $table->timestamp('last_payment_reminder_at')->nullable();
            }
        });

        DB::table('email_templates')->where('slug', 'interest-received')
            ->where('body_html', EmailTemplateBodies::INTEREST_RECEIVED_V1)
            ->update([
                'body_html' => EmailTemplateBodies::INTEREST_RECEIVED,
                'variables' => json_encode(['RECEIVER_NAME', 'SENDER_MATRI_ID', 'SENDER_SUMMARY', 'ACCEPT_URL', 'DECLINE_URL', 'ACTION_URL', 'SITE_NAME']),
                'updated_at' => now(),
            ]);
        Cache::forget('email_template.interest-received');
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'interest-received')
            ->where('body_html', EmailTemplateBodies::INTEREST_RECEIVED)
            ->update(['body_html' => EmailTemplateBodies::INTEREST_RECEIVED_V1]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_interest_reminder_at', 'last_payment_reminder_at']);
        });
    }
};
