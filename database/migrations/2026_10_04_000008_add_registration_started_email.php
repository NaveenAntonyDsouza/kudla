<?php

use App\Support\EmailPreheaders;
use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * - New template `registration-started`: the "thank you for joining" email
 *   an hour after someone stops registering (reminder #1).
 * - `registration-reminder` (#2 and #3) gains the "Confirm my email" button
 *   and loses the one-off "we've recently fixed some problems" line of the
 *   2 Oct hand run — only where the body is still that default, so an
 *   admin's own wording is kept.
 */
return new class extends Migration
{
    private const VARIABLES = ['USER_NAME', 'MATRI_ID', 'MEMBER_ID_LABEL', 'STATUS_LINE', 'CONFIRM_EMAIL_HTML', 'HELP_LINE', 'LOGIN_URL', 'FORGOT_URL', 'UNSUBSCRIBE_URL', 'SITE_NAME', 'PRIMARY_COLOR'];

    public function up(): void
    {
        if (! DB::table('email_templates')->where('slug', 'registration-started')->exists()) {
            DB::table('email_templates')->insert([
                'slug' => 'registration-started',
                'name' => 'Reminder — thanks for joining, finish your profile (1 hour)',
                'subject' => 'Thank you for joining {{SITE_NAME}} — your profile is almost ready',
                'preheader' => EmailPreheaders::DEFAULTS['registration-started'],
                'body_html' => EmailTemplateBodies::REGISTRATION_STARTED,
                'variables' => json_encode(self::VARIABLES),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('email_templates')->where('slug', 'registration-reminder')
            ->where('body_html', EmailTemplateBodies::REGISTRATION_REMINDER_V1)
            ->update([
                'body_html' => EmailTemplateBodies::REGISTRATION_REMINDER,
                'variables' => json_encode(self::VARIABLES),
                'updated_at' => now(),
            ]);

        Cache::forget('email_template.registration-started');
        Cache::forget('email_template.registration-reminder');
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'registration-reminder')
            ->where('body_html', EmailTemplateBodies::REGISTRATION_REMINDER)
            ->update(['body_html' => EmailTemplateBodies::REGISTRATION_REMINDER_V1]);
        DB::table('email_templates')->where('slug', 'registration-started')->delete();
    }
};
