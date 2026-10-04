<?php

use App\Support\EmailPreheaders;
use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Weekly "who viewed your profile" email: send guard + template. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'last_views_digest_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->timestamp('last_views_digest_at')->nullable());
        }

        if (! DB::table('email_templates')->where('slug', 'profile-views-digest')->exists()) {
            DB::table('email_templates')->insert([
                'slug' => 'profile-views-digest',
                'name' => 'Weekly — who viewed your profile',
                'subject' => '{{VIEW_HEADLINE}}',
                'preheader' => EmailPreheaders::DEFAULTS['profile-views-digest'],
                'body_html' => EmailTemplateBodies::PROFILE_VIEWS_DIGEST,
                'variables' => json_encode(['USER_NAME', 'MATRI_ID', 'VIEW_HEADLINE', 'VIEWER_CARDS_HTML', 'VIEWS_URL', 'UNSUBSCRIBE_URL', 'SITE_NAME', 'PRIMARY_COLOR']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Cache::forget('email_template.profile-views-digest');
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'profile-views-digest')->delete();
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('last_views_digest_at'));
    }
};
