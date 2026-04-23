<?php

namespace App\Console\Commands;

use App\Services\WeeklyMatchSuggestionsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('engagement:send-weekly-matches {--dry-run : Report who would receive emails without sending}')]
#[Description('Send weekly match suggestion emails to active members with partner preferences. Runs weekly Sunday 10 AM via scheduler.')]
class SendWeeklyMatchEmails extends Command
{
    public function handle(WeeklyMatchSuggestionsService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no emails will be sent, no DB updates made.');
        }

        if (!$service->isEnabled()) {
            $this->error('Weekly matches are disabled via SiteSetting `weekly_matches_enabled`.');
            return self::SUCCESS;
        }

        $matchCount = $service->getMatchCount();
        $minScore = $service->getMinScore();
        $this->info("Match count per email: $matchCount");
        $this->info("Minimum match score: $minScore");

        $startTime = microtime(true);
        $result = $service->run($dryRun);
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->line('');
        $this->info('Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Eligible candidates (SQL pre-filter)', $result['eligible']],
                [$dryRun ? 'Would send emails' : 'Emails sent', $result['sent']],
                ['Skipped: no matching profiles', $result['skipped_no_matches']],
                ['Skipped: other reasons (opt-out, rate-limit, etc.)', $result['skipped_other']],
            ]
        );

        if (!empty($result['recipients']) && ($dryRun || $this->getOutput()->isVerbose())) {
            $this->line('');
            $this->info('Recipients:');
            $this->table(
                ['User ID', 'Email', 'Name', 'Matches', 'Top Score'],
                array_map(fn ($r) => [
                    $r['user_id'],
                    $r['email'],
                    $r['name'],
                    $r['match_count'],
                    $r['top_score'],
                ], $result['recipients'])
            );
        }

        $this->line('');
        $this->info("Completed in {$elapsed}s");

        return self::SUCCESS;
    }
}
