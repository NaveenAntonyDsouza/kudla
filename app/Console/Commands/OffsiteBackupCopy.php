<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Copies the nightly database backups (db:backup) to the other hosting account
 * (7 Nov 2026), so one server failing does not take its own backups with it.
 *
 * Runs only on the site whose setting offsite_backup_script names the copy script
 * (Global Catholic Match, on Hostinger: Hostinger can reach Bluehost, not the other way
 * round, and it has no cron of its own besides the sites' schedulers). Any problem is
 * logged as an error, so the hourly error alert emails the owner.
 */
class OffsiteBackupCopy extends Command
{
    protected $signature = 'db:offsite-copy';

    protected $description = 'Copy the database backups to the other hosting account (only where offsite_backup_script is set)';

    public function handle(): int
    {
        $script = (string) SiteSetting::getValue('offsite_backup_script', '');
        if ($script === '') {
            return self::SUCCESS; // not this site's job
        }
        if (! is_file($script)) {
            Log::error("Offsite backup copy: the script {$script} is missing");

            return self::FAILURE;
        }

        $result = Process::timeout(900)->run(['bash', $script]);
        $this->line(trim($result->output()));

        if (! $result->successful()) {
            $problems = collect(explode("\n", $result->output() . "\n" . $result->errorOutput()))
                ->filter(fn ($line) => str_starts_with($line, 'ERROR:'))->implode('; ');
            Log::error('Offsite backup copy failed: ' . ($problems !== '' ? $problems : 'exit ' . $result->exitCode()));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
