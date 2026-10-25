<?php

namespace App\Console\Commands;

use App\Services\MemberErasure;
use Illuminate\Console\Command;

/**
 * Daily: erase the personal data of members who used "Delete Account" 90+
 * days ago and weren't restored (App\Services\MemberErasure). Cannot be undone.
 */
class EraseDeletedMembers extends Command
{
    protected $signature = 'members:erase-deleted {--dry-run : count who would be erased; change nothing}';

    protected $description = 'Erase personal data 90 days after "Delete Account" (privacy policy promise)';

    public function handle(MemberErasure $erasure): int
    {
        $due = $erasure->due()->get();
        $this->info('Deleted 90+ days ago, not yet erased: '.$due->count().'.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $done = 0;
        foreach ($due as $profile) {
            try {
                $erasure->erase($profile);
                $done++;
            } catch (\Throwable $e) {
                report($e);
                $this->error("Could not erase profile {$profile->id}: ".$e->getMessage());
            }
        }
        $this->info("Erased: {$done}.");

        return self::SUCCESS;
    }
}
