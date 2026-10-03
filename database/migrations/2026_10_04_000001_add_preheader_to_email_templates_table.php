<?php

use App\Support\EmailPreheaders;
use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Preview text for email templates (the line an inbox shows next to the
 * subject), and the new welcome email.
 *
 * Both data changes only touch what an admin hasn't customised: preview text
 * is filled where it is still empty, and the welcome body is replaced only
 * while it still equals the original default.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('email_templates', 'preheader')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->string('preheader', 255)->nullable()->after('subject');
            });
        }

        foreach (EmailPreheaders::DEFAULTS as $slug => $text) {
            DB::table('email_templates')->where('slug', $slug)->whereNull('preheader')
                ->update(['preheader' => $text, 'updated_at' => now()]);
        }

        DB::table('email_templates')->where('slug', 'welcome')->where('body_html', EmailTemplateBodies::WELCOME_V1)
            ->update([
                'body_html' => EmailTemplateBodies::WELCOME,
                'variables' => json_encode(['USER_NAME', 'MEMBER_ID_LABEL', 'MATRI_ID', 'ACTION_URL', 'SITE_NAME']),
                'updated_at' => now(),
            ]);

        // findBySlug() caches each template for an hour; these were query-
        // builder updates, so no model event cleared it.
        foreach (DB::table('email_templates')->pluck('slug') as $slug) {
            Cache::forget("email_template.{$slug}");
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'welcome')->where('body_html', EmailTemplateBodies::WELCOME)
            ->update(['body_html' => EmailTemplateBodies::WELCOME_V1]);

        if (Schema::hasColumn('email_templates', 'preheader')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn('preheader');
            });
        }
    }
};
