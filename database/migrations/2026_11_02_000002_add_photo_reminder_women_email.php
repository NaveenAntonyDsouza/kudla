<?php

use App\Support\EmailPreheaders;
use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Template `photo-reminder-women`: the "add a photo" reminder for women,
 * leading with "only members whose interest I accept". On kudla 31 of 144
 * visible women show a photo against 43% of men (2 Nov 2026), and the
 * choice added to the sign-up photo step on 31 Oct only reaches the few
 * women who join each week — this reaches the 113 already here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('email_templates')->where('slug', 'photo-reminder-women')->exists()) {
            DB::table('email_templates')->insert([
                'slug' => 'photo-reminder-women',
                'name' => 'Reminder — add a profile photo (women)',
                'subject' => '{{USER_NAME}}, add a photo — you choose who can see it',
                'preheader' => EmailPreheaders::DEFAULTS['photo-reminder-women'],
                'body_html' => EmailTemplateBodies::PHOTO_REMINDER_WOMEN,
                'variables' => json_encode(['USER_NAME', 'MATRI_ID', 'MEMBER_ID_LABEL', 'PHOTOS_URL', 'HELP_LINE', 'FORGOT_URL', 'UNSUBSCRIBE_URL', 'SITE_NAME', 'PRIMARY_COLOR']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Cache::forget('email_template.photo-reminder-women');
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'photo-reminder-women')->delete();
        Cache::forget('email_template.photo-reminder-women');
    }
};
