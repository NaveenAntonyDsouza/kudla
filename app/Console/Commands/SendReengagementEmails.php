<?php

namespace App\Console\Commands;

use App\Services\ReengagementService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('engagement:send-reengagement {--dry-run : Report who would receive emails without sending}')]
#[Description('Send re-engagement emails to inactive members. Runs daily via scheduler.')]
class SendReengagementEmails extends Command
{
    public function handle(ReengagementService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no emails will be sent, no DB updates made.');
        }

        if (!$service->isEnabled()) {
            $this->error('Re-engagement is disabled via SiteSetting `reengagement_enabled`.');
            return self::SUCCESS;
        }

        $thresholds = $service->getThresholds();
        $this->info('Re-engagement thresholds (days): ' . implode(' / ', $thresholds));

        $startTime = microtime(true);
        $result = $service->run($dryRun);
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->line('');
        $this->info('Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Eligible candidates (from SQL pre-filter)', $result['eligible']],
                ['Skipped (opt-out, recent send, wrong level)', $result['skipped']],
                ['Level 1 (7-day) ' . ($dryRun ? 'would send' : 'sent'), $result['sent_by_level'][1]],
                ['Level 2 (14-day) ' . ($dryRun ? 'would send' : 'sent'), $result['sent_by_level'][2]],
                ['Level 3 (30-day) ' . ($dryRun ? 'would send' : 'sent'), $result['sent_by_level'][3]],
                ['Total ' . ($dryRun ? 'would send' : 'sent'), array_sum($result['sent_by_level'])],
            ]
        );

        if (!empty($result['recipients']) && ($dryRun || $this->getOutput()->isVerbose())) {
            $this->line('');
            $this->info('Recipients:');
            $this->table(
                ['User ID', 'Email', 'Name', 'Days Inactive', 'Level'],
                array_map(fn ($r) => [
                    $r['user_id'],
                    $r['email'],
                    $r['name'],
                    $r['days_inactive'],
                    'L' . $r['level'],
                ], $result['recipients'])
            );
        }

        $this->line('');
        $this->info("Completed in {$elapsed}s");

        return self::SUCCESS;
    }
}
