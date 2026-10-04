<?php

namespace App\Console\Commands;

use App\Mail\StillLookingMail;
use App\Models\AdminActivityLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\DormantProfiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Daily: "Are you still looking?" to members inactive 90+ days (once each
 * quiet spell), and hide from search those inactive 180+ days who didn't
 * answer within 2 weeks. See App\Services\DormantProfiles.
 */
class HandleDormantProfiles extends Command
{
    protected $signature = 'members:dormant
        {--dry-run : list who would be emailed / hidden; change nothing}
        {--scheduled : only where dormant_profiles_enabled = 1}
        {--delay=3 : seconds between emails}';

    protected $description = 'Ask long-inactive members if they are still looking; hide profiles that stay silent';

    public function handle(DormantProfiles $dormant): int
    {
        if ($this->option('scheduled') && SiteSetting::getValue('dormant_profiles_enabled', '0') !== '1') {
            $this->info('Dormant-profile handling is off (dormant_profiles_enabled).');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $cap = max(0, (int) SiteSetting::getValue('dormant_checkin_daily_cap', '40'));

        $ask = $dormant->dueForCheckin()->filter(fn (User $u) => $u->blockedStatus() === null && $u->wantsNotification('email_reengagement'));
        $hide = $dormant->dueForHiding();

        $this->info(sprintf('Still-looking emails due: %d (sending up to %d today). Profiles to hide: %d.', $ask->count(), $cap, $hide->count()));
        foreach ($hide as $u) {
            $this->line("  hide  {$u->profile?->matri_id} (last seen {$u->last_seen->format('j M Y')})");
        }
        if ($dry) {
            foreach ($ask->take($cap) as $u) {
                $this->line("  ask   {$u->profile?->matri_id} (last seen {$u->last_seen->format('j M Y')})");
            }
            $this->warn('DRY RUN — nothing sent or hidden.');

            return self::SUCCESS;
        }

        foreach ($hide as $u) {
            $dormant->hide($u);
        }

        $sent = $failed = [];
        foreach ($ask->take($cap)->values() as $i => $u) {
            if ($i > 0 && (int) $this->option('delay') > 0) {
                sleep((int) $this->option('delay'));
            }
            try {
                Mail::to($u->email)->send(new StillLookingMail($u));
                DB::table('users')->where('id', $u->id)->update(['dormant_checkin_at' => now()]);
                $sent[] = $u->profile?->matri_id;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $u->profile?->matri_id;
            }
        }

        if ($sent || $failed || $hide->isNotEmpty()) {
            try {
                AdminActivityLog::create([
                    'action' => 'dormant_profiles_handled',
                    'changes' => ['asked' => $sent, 'failed' => $failed, 'hidden' => $hide->map(fn ($u) => $u->profile?->matri_id)->values()->all()],
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info('Asked: '.count($sent).', failed: '.count($failed).', hidden: '.$hide->count());

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
