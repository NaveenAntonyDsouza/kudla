<?php

use App\Support\EmailPreheaders;
use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Template `community-update`: asks a member whose caste is "Salian" (a bari)
 * or "Konkani" (a language) to choose their community. Used once by
 * `communities:tulu-nadu-update --ask` (kudla, Oct 2026); harmless elsewhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('email_templates')->where('slug', 'community-update')->exists()) {
            DB::table('email_templates')->insert([
                'slug' => 'community-update',
                'name' => 'Ask a member to choose their community (one-off)',
                'subject' => 'Please check your community on {{SITE_NAME}}',
                'preheader' => EmailPreheaders::DEFAULTS['community-update'],
                'body_html' => EmailTemplateBodies::COMMUNITY_UPDATE,
                'variables' => json_encode(['USER_NAME', 'MATRI_ID', 'CURRENT_VALUE', 'REASON_LINE', 'EDIT_URL', 'HELP_LINE', 'SITE_NAME', 'PRIMARY_COLOR']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Cache::forget('email_template.community-update');
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'community-update')->delete();
    }
};
