<?php

namespace App\Console\Commands;

use App\Support\VerificationCalls;
use Illuminate\Console\Command;

/** Every 10 minutes: new members onto the verification calls list, shared out to the callers. */
class AssignVerificationCalls extends Command
{
    protected $signature = 'verification-calls:assign';

    protected $description = 'Put new members on the verification calls list and share them out to the staff who take the calls';

    public function handle(): int
    {
        $added = VerificationCalls::sync();
        $this->info("Added {$added} new ".($added === 1 ? 'member' : 'members').' to the verification calls list.');

        return self::SUCCESS;
    }
}
