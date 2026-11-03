<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The weekly match email lists the interests a member still owes an answer
 * to, with Accept / Decline (weekly review, 4 Nov 2026: 7 of 68 answers came
 * from those buttons in the interest email; 263 interests were waiting).
 * {{PENDING_REPLIES_HTML}} goes straight after the match cards. A site whose
 * template no longer has {{MATCH_CARDS_HTML}} is left alone; an admin can add
 * the new line in Email Templates.
 */
return new class extends Migration
{
    private const SLUG = 'weekly-match-suggestions';

    public function up(): void
    {
        $template = DB::table('email_templates')->where('slug', self::SLUG)->first();
        $body = (string) ($template->body_html ?? '');

        if ($template && str_contains($body, '{{MATCH_CARDS_HTML}}') && ! str_contains($body, '{{PENDING_REPLIES_HTML}}')) {
            $after = strpos($body, '{{MATCH_CARDS_HTML}}') + strlen('{{MATCH_CARDS_HTML}}');
            $variables = json_decode((string) $template->variables, true) ?: [];
            if (! in_array('PENDING_REPLIES_HTML', $variables, true)) {
                $at = array_search('MATCH_CARDS_HTML', $variables, true);
                array_splice($variables, $at === false ? count($variables) : $at + 1, 0, ['PENDING_REPLIES_HTML']);
            }

            DB::table('email_templates')->where('id', $template->id)->update([
                'body_html' => substr_replace($body, '{{PENDING_REPLIES_HTML}}', $after, 0),
                'variables' => json_encode(array_values($variables)),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('email_template.' . self::SLUG);
    }

    public function down(): void
    {
        $template = DB::table('email_templates')->where('slug', self::SLUG)->first();
        if ($template) {
            DB::table('email_templates')->where('id', $template->id)->update([
                'body_html' => str_replace('{{PENDING_REPLIES_HTML}}', '', (string) $template->body_html),
                'variables' => json_encode(array_values(array_diff(json_decode((string) $template->variables, true) ?: [], ['PENDING_REPLIES_HTML']))),
            ]);
        }

        Cache::forget('email_template.' . self::SLUG);
    }
};
