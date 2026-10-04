<?php

namespace App\Console\Commands;

use App\Mail\ProfileViewsDigestMail;
use App\Models\AdminActivityLog;
use App\Models\SiteSetting;
use App\Services\ProfileViewsDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** Weekly "N members viewed your profile this week" — see App\Services\ProfileViewsDigest. */
class SendProfileViewsDigest extends Command
{
    protected $signature = 'engagement:send-profile-views-digest
        {--dry-run : list who would get it; send nothing}
        {--scheduled : only where profile_views_digest_enabled = 1}
        {--delay=2 : seconds between emails}';

    protected $description = 'Email members how many people viewed their profile this week (and who, if they can see it)';

    public function handle(ProfileViewsDigest $digest): int
    {
        if ($this->option('scheduled') && SiteSetting::getValue('profile_views_digest_enabled', '0') !== '1') {
            $this->info('Profile-views digest is off (profile_views_digest_enabled).');

            return self::SUCCESS;
        }

        $cap = max(0, (int) SiteSetting::getValue('profile_views_digest_cap', '300'));
        $due = $digest->due()->sortByDesc('count')->take($cap)->values();
        $this->info("Members to email: {$due->count()}");

        if ($this->option('dry-run')) {
            foreach ($due as $row) {
                $this->line("  {$row['user']->profile->matri_id}: {$row['count']} viewer(s), {$row['viewers']->count()} shown");
            }
            $this->warn('DRY RUN — nothing sent.');

            return self::SUCCESS;
        }

        $sent = $failed = 0;
        foreach ($due as $i => $row) {
            if ($i > 0 && (int) $this->option('delay') > 0) {
                sleep((int) $this->option('delay'));
            }
            try {
                Mail::to($row['user']->email)->send(new ProfileViewsDigestMail($row['user'], $row['count'], $row['viewers']));
                DB::table('users')->where('id', $row['user']->id)->update(['last_views_digest_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        if ($sent || $failed) {
            try {
                AdminActivityLog::create(['action' => 'profile_views_digest_sent', 'changes' => ['sent' => $sent, 'failed' => $failed]]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $this->info("Sent: {$sent}, failed: {$failed}");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
