<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Photo emails name the other member as "Priya D. (KM100123)" (owner, 20 Nov 2026). Only the bold
 * Matri ID tag in the body is swapped, so an admin's other wording (and Kudla's theme colour) stays;
 * subjects and preview lines keep the Matri ID.
 */
return new class extends Migration
{
    private const SWAPS = [
        'photo-request-received' => ['REQUESTER_MATRI_ID', 'REQUESTER_LABEL'],
        'photo-upload-requested' => ['REQUESTER_MATRI_ID', 'REQUESTER_LABEL'],
        'photo-request-approved' => ['APPROVER_MATRI_ID', 'APPROVER_LABEL'],
        'photo-added' => ['OWNER_MATRI_ID', 'OWNER_LABEL'],
    ];

    public function up(): void
    {
        $this->swap(false);
    }

    public function down(): void
    {
        $this->swap(true);
    }

    private function swap(bool $back): void
    {
        foreach (self::SWAPS as $slug => [$id, $label]) {
            [$from, $to] = $back ? [$label, $id] : [$id, $label];
            $row = DB::table('email_templates')->where('slug', $slug)->first(['id', 'body_html', 'variables']);
            if (! $row || ! str_contains((string) $row->body_html, "<strong>{{{$from}}}</strong>")) {
                continue;
            }
            $vars = json_decode((string) $row->variables, true) ?: [];
            $vars = $back ? array_values(array_diff($vars, [$label])) : array_values(array_unique(array_merge($vars, [$label])));
            DB::table('email_templates')->where('id', $row->id)->update([
                'body_html' => str_replace("<strong>{{{$from}}}</strong>", "<strong>{{{$to}}}</strong>", $row->body_html),
                'variables' => json_encode($vars),
                'updated_at' => now(),
            ]);
            Cache::forget("email_template.{$slug}");
        }
    }
};
