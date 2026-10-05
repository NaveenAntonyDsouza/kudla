<?php

namespace App\Console\Commands;

use App\Models\AdminActivityLog;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Interests left unanswered for interest_expiry_days (default 30) expire
 * (owner's choice, 5 Oct 2026: kudla had 666 pending, 450 of them over 30
 * days old). Each sender gets one in-app note per run saying how many
 * expired and that they can send again. The receiver's interest reminders
 * stop at 30 days too, so they're never asked to answer an expired one.
 * Expired interests show under Interests → Expired, and can be re-sent.
 */
class ExpireInterests extends Command
{
    protected $signature = 'interests:expire
        {--dry-run : count what would expire; change nothing}
        {--scheduled : only where interest_expiry_enabled = 1}';

    protected $description = 'Expire interests unanswered for 30 days and tell the senders';

    public function handle(NotificationService $notifications): int
    {
        if ($this->option('scheduled') && SiteSetting::getValue('interest_expiry_enabled', '0') !== '1') {
            $this->info('Interest expiry is off (interest_expiry_enabled).');

            return self::SUCCESS;
        }

        $days = max(7, (int) SiteSetting::getValue('interest_expiry_days', '30'));
        $due = DB::table('interests')->where('status', 'pending')->where('created_at', '<', now()->subDays($days));
        $perSender = (clone $due)->groupBy('sender_profile_id')->selectRaw('sender_profile_id, COUNT(*) as n')->pluck('n', 'sender_profile_id');
        $total = (int) $perSender->sum();

        $this->info("Interests unanswered for {$days}+ days: {$total}, from {$perSender->count()} member(s).");
        if ($this->option('dry-run') || $total === 0) {
            return self::SUCCESS;
        }

        // Query builder: no model events, so no "declined"-style emails go out
        $expired = (clone $due)->update(['status' => 'expired', 'updated_at' => now()]);

        $noted = 0;
        foreach (Profile::whereIn('id', $perSender->keys())->with('user')->get() as $sender) {
            if (! $sender->user) {
                continue;
            }
            $n = (int) $perSender[$sender->id];
            try {
                $notifications->send(
                    $sender->user,
                    'interest_expired',
                    $n === 1 ? 'An interest you sent has expired' : "{$n} interests you sent have expired",
                    ($n === 1 ? 'The member didn\'t reply' : 'Those members didn\'t reply')
                        ." within {$days} days, so we've closed ".($n === 1 ? 'it' : 'them').'. You can send '.($n === 1 ? 'it' : 'them')
                        .' again any time, or look at members who are active now.',
                    null,
                    ['count' => $n, 'url' => '/interests?tab=expired'],
                );
                $noted++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            AdminActivityLog::create(['action' => 'interests_expired', 'changes' => ['expired' => $expired, 'senders_told' => $noted, 'after_days' => $days]]);
        } catch (\Throwable $e) {
            report($e);
        }
        $this->info("Expired: {$expired}. Senders told: {$noted}.");

        return self::SUCCESS;
    }
}
