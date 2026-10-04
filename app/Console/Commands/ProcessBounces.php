<?php

namespace App\Console\Commands;

use App\Models\AdminActivityLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Bounces\BounceParser;
use App\Support\Bounces\BounceSource;
use App\Support\EmailSuppression;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Nightly: reads the bounce notices in the site mailbox (the same login the
 * site sends with) and stops emailing members whose address is dead
 * (EmailSuppression). Read-only on the mailbox: nothing is marked read,
 * moved or deleted.
 *
 * - Dead address ("does not exist", "disabled", bad domain): marked at once.
 * - Inbox full: only when it keeps bouncing — FULL_MIN_BOUNCES bounces over
 *   at least FULL_MIN_DAYS days (a full inbox usually gets cleared).
 * - Spam blocks and server trouble: ignored (they say nothing about the address).
 * - A mark an admin cleared is not re-made from bounces older than the clearing.
 *
 * Off unless SiteSetting bounce_processing_enabled = 1.
 */
class ProcessBounces extends Command
{
    protected $signature = 'email:process-bounces
        {--days=90 : how far back to read bounce notices}
        {--dry-run : show what would be marked, change nothing}
        {--force : run even when bounce_processing_enabled is off}';

    protected $description = 'Read bounce notices in the site mailbox and stop emailing dead addresses';

    public const FULL_MIN_BOUNCES = 5;

    public const FULL_MIN_DAYS = 21;

    public function handle(BounceSource $source): int
    {
        if (! $this->option('force') && SiteSetting::getValue('bounce_processing_enabled', '0') !== '1') {
            $this->info('Bounce processing is off (bounce_processing_enabled).');

            return self::SUCCESS;
        }

        $since = now()->subDays(max(1, (int) $this->option('days')));
        $byAddress = [];
        $notices = 0;

        try {
            foreach ($source->bounces($since) as $notice) {
                $notices++;
                $received = $this->date($notice['received']);
                foreach (BounceParser::parse($notice['raw']) as $result) {
                    $byAddress[$result['recipient']][] = $result + ['received' => $received];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('email:process-bounces could not read the mailbox: '.$e->getMessage());
            $this->error('Could not read the mailbox: '.$e->getMessage());

            return self::FAILURE;
        }

        $marked = [];
        foreach ($byAddress as $address => $results) {
            $user = User::whereRaw('LOWER(email) = ?', [$address])->first(['id', 'email', 'email_undeliverable_at', 'email_undeliverable_address']);
            if (! $user || ($user->email_undeliverable_at && strcasecmp((string) $user->email_undeliverable_address, (string) $user->email) === 0)) {
                continue; // not a member, or already stopped
            }

            // Ignore bounces from before an admin last cleared this member's mark
            $clearedAt = AdminActivityLog::where('action', 'email_undeliverable_cleared')->where('model_type', User::class)
                ->where('model_id', $user->id)->max('created_at');
            $results = array_values(array_filter($results, fn ($r) => ! $clearedAt || ($r['received'] && $r['received']->gt(Carbon::parse($clearedAt)))));

            $reason = $this->reason($results);
            if ($reason === null) {
                continue;
            }

            $marked[] = [$address, $reason];
            if (! $this->option('dry-run')) {
                EmailSuppression::mark($user->email, $reason);
            }
        }

        $summary = sprintf('%d bounce notices read, %d addresses, %d %s', $notices, count($byAddress), count($marked), $this->option('dry-run') ? 'would be stopped' : 'stopped');
        $this->info($summary);
        foreach ($marked as [$address, $reason]) {
            $this->line("  {$address} — {$reason}");
        }
        Log::info('email:process-bounces: '.$summary);

        return self::SUCCESS;
    }

    /** Why to stop emailing this address, or null to keep trying. */
    private function reason(array $results): ?string
    {
        $dead = collect($results)->where('kind', BounceParser::DEAD)->sortByDesc('received')->first();
        if ($dead) {
            return Str::limit(trim(($dead['status'] ? $dead['status'].' ' : '').$dead['diagnostic']), 180, '');
        }

        $full = collect($results)->where('kind', BounceParser::FULL)->pluck('received')->filter()->sort()->values();
        if ($full->count() >= self::FULL_MIN_BOUNCES && $full->first()->diffInDays($full->last()) >= self::FULL_MIN_DAYS) {
            return sprintf('Inbox full for %d days (%d bounces since %s)', (int) $full->first()->diffInDays($full->last()), $full->count(), $full->first()->format('j M Y'));
        }

        return null;
    }

    private function date(?string $value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
