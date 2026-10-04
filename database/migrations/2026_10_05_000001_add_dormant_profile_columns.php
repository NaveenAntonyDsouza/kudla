<?php

use App\Support\EmailPreheaders;
use App\Support\EmailTemplateBodies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dormant profiles (App\Services\DormantProfiles):
 * - profiles.hidden_reason: why is_hidden is on — null (the member hid it),
 *   "dormant" (we hid it for inactivity; comes back on their next visit) or
 *   "found_match" (they told us they found their partner).
 * - users.dormant_checkin_at: when the "Are you still looking?" email went.
 * - template `still-looking`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('profiles', 'hidden_reason')) {
            Schema::table('profiles', fn (Blueprint $t) => $t->string('hidden_reason', 20)->nullable());
        }
        if (! Schema::hasColumn('users', 'dormant_checkin_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->timestamp('dormant_checkin_at')->nullable());
        }

        if (! DB::table('email_templates')->where('slug', 'still-looking')->exists()) {
            DB::table('email_templates')->insert([
                'slug' => 'still-looking',
                'name' => 'Still looking? (members inactive 90+ days)',
                'subject' => '{{USER_NAME}}, are you still looking?',
                'preheader' => EmailPreheaders::DEFAULTS['still-looking'],
                'body_html' => EmailTemplateBodies::STILL_LOOKING,
                'variables' => json_encode(['USER_NAME', 'MATRI_ID', 'MEMBER_ID_LABEL', 'STILL_LOOKING_URL', 'FOUND_MATCH_URL', 'HIDE_URL', 'HELP_LINE', 'UNSUBSCRIBE_URL', 'SITE_NAME', 'PRIMARY_COLOR']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Cache::forget('email_template.still-looking');
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'still-looking')->delete();
        Schema::table('profiles', fn (Blueprint $t) => $t->dropColumn('hidden_reason'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('dormant_checkin_at'));
    }
};
