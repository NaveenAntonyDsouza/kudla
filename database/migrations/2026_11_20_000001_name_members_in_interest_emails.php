<?php

use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Interest received / accepted emails name the other member as "Priya D. (KM100123)" (owner,
 * 20 Nov 2026). A body is replaced only while it still equals the previous default, so an admin's
 * own wording is never overwritten. Subjects and preview lines keep the Matri ID only.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_templates')->where('slug', 'interest-received')
            ->where('body_html', EmailTemplateBodies::INTEREST_RECEIVED_V2)
            ->update([
                'body_html' => EmailTemplateBodies::INTEREST_RECEIVED,
                'variables' => json_encode(['RECEIVER_NAME', 'SENDER_MATRI_ID', 'SENDER_LABEL', 'SENDER_SUMMARY', 'ACCEPT_URL', 'DECLINE_URL', 'ACTION_URL', 'SITE_NAME']),
                'updated_at' => now(),
            ]);
        DB::table('email_templates')->where('slug', 'interest-accepted')
            ->where('body_html', EmailTemplateBodies::INTEREST_ACCEPTED_V1)
            ->update([
                'body_html' => EmailTemplateBodies::INTEREST_ACCEPTED,
                'variables' => json_encode(['SENDER_NAME', 'ACCEPTER_MATRI_ID', 'ACCEPTER_LABEL', 'ACTION_URL', 'SITE_NAME']),
                'updated_at' => now(),
            ]);
        Cache::forget('email_template.interest-received');
        Cache::forget('email_template.interest-accepted');
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'interest-received')
            ->where('body_html', EmailTemplateBodies::INTEREST_RECEIVED)
            ->update(['body_html' => EmailTemplateBodies::INTEREST_RECEIVED_V2]);
        DB::table('email_templates')->where('slug', 'interest-accepted')
            ->where('body_html', EmailTemplateBodies::INTEREST_ACCEPTED)
            ->update(['body_html' => EmailTemplateBodies::INTEREST_ACCEPTED_V1]);
        Cache::forget('email_template.interest-received');
        Cache::forget('email_template.interest-accepted');
    }
};
