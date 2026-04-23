<?php

namespace App\Console\Commands;

use App\Services\ProfileNudgeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('engagement:send-profile-nudges {--dry-run : Report who would receive a nudge without creating notifications}')]
#[Description('Send in-app profile-completion nudges to active members with incomplete profiles. Runs daily 19:00.')]
class SendProfileNudges extends Command
{
    public function handle(ProfileNudgeService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no nudges will be created.');
        }

        if (!$service->isEnabled()) {
            $this->error('Profile nudges are disabled via SiteSetting `profile_nudges_enabled`.');
            return self::SUCCESS;
        }

        $threshold = $service->getThreshold();
        $this->info("Completion threshold: $threshold% (profiles above this are skipped)");

        $startTime = microtime(true);
        $result = $service->run($dryRun);
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->line('');
        $this->info('Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Eligible candidates (SQL pre-filter)', $result['eligible']],
                [$dryRun ? 'Would send' : 'Nudges sent', $result['sent']],
                ['Skipped: profile already >= threshold', $result['skipped_no_missing']],
                ['Skipped: other (rate-limit, opt-out)', $result['skipped_other']],
            ]
        );

        if (!empty($result['recipients']) && ($dryRun || $this->getOutput()->isVerbose())) {
            $this->line('');
            $this->info('Recipients:');
            $this->table(
                ['User ID', 'Email', 'Name', 'Completion %', 'Nudge', 'Lifetime #'],
                array_map(fn ($r) => [
                    $r['user_id'],
                    $r['email'],
                    $r['name'],
                    $r['completion_pct'] . '%',
                    $r['nudge_type'],
                    $r['sent_count'],
                ], $result['recipients'])
            );
        }

        $this->line('');
        $this->info("Completed in {$elapsed}s");

        return self::SUCCESS;
    }
}
