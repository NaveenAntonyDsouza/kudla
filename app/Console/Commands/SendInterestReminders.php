<?php

namespace App\Console\Commands;

use App\Mail\InterestReminderMail;
use App\Models\Interest;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;

/**
 * "Members are waiting for your reply" — for interests a member received
 * 3 to 30 days ago and hasn't answered. At most one email per member per
 * week; follows their "Interest notifications" email setting.
 *
 * Settings: interest_reminders_enabled (default 1), interest_reminders_daily_cap
 * (default 30 — the smaller sites' mailboxes send 100 emails a day in total).
 */
#[Signature('engagement:send-interest-reminders {--dry-run : List who would get a reminder without sending}')]
#[Description('Remind members about interests they have not answered for 3+ days (at most once a week each)')]
class SendInterestReminders extends Command
{
    public const MIN_AGE_DAYS = 3;
    public const MAX_AGE_DAYS = 30;
    public const REPEAT_AFTER_DAYS = 7;
    public const LISTED = 5;

    public function handle(): int
    {
        if (SiteSetting::getValue('interest_reminders_enabled', '1') !== '1') {
            $this->warn('Interest reminders are switched off (interest_reminders_enabled).');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $cap = max(1, (int) SiteSetting::getValue('interest_reminders_daily_cap', '30'));

        $byReceiver = Interest::query()
            ->where('status', 'pending')
            ->where('is_trashed_by_receiver', false)
            ->whereBetween('created_at', [now()->subDays(self::MAX_AGE_DAYS), now()->subDays(self::MIN_AGE_DAYS)])
            // Only senders still on the site (soft-deleted profiles drop out of whereHas)
            ->whereHas('senderProfile', fn ($q) => $q->where('is_active', true))
            // Never remind someone about a member either of them blocked or ignored
            ->whereNotExists(fn ($q) => $q->from('blocked_profiles')->where(fn ($w) => $w
                ->where(fn ($a) => $a->whereColumn('blocked_profiles.profile_id', 'interests.receiver_profile_id')->whereColumn('blocked_profiles.blocked_profile_id', 'interests.sender_profile_id'))
                ->orWhere(fn ($b) => $b->whereColumn('blocked_profiles.profile_id', 'interests.sender_profile_id')->whereColumn('blocked_profiles.blocked_profile_id', 'interests.receiver_profile_id'))))
            ->whereNotExists(fn ($q) => $q->from('ignored_profiles')
                ->whereColumn('ignored_profiles.profile_id', 'interests.receiver_profile_id')
                ->whereColumn('ignored_profiles.ignored_profile_id', 'interests.sender_profile_id'))
            ->with(['senderProfile.locationInfo', 'senderProfile.educationDetail', 'receiverProfile.user'])
            ->orderBy('created_at')
            ->get()
            ->groupBy('receiver_profile_id');

        $sent = 0;
        $skipped = 0;
        $deferred = 0;

        foreach ($byReceiver as $interests) {
            $user = $interests->first()->receiverProfile?->user;

            if (! $user || blank($user->email) || $user->staff_role_id !== null
                || $user->blockedStatus() !== null || ! $user->wantsNotification('email_interest')
                || ($user->last_interest_reminder_at && $user->last_interest_reminder_at->gt(now()->subDays(self::REPEAT_AFTER_DAYS)))) {
                $skipped++;
                continue;
            }

            if ($sent >= $cap) {
                $deferred++;
                continue;
            }

            if ($dryRun) {
                $this->line("Would remind {$user->email}: {$interests->count()} waiting");
                $sent++;
                continue;
            }

            // Pace the sends like the other bulk emails (mailbox sending limits)
            if ($sent > 0) {
                Sleep::for(2)->seconds();
            }

            try {
                Mail::to($user->email)->send(new InterestReminderMail($user, $interests->take(self::LISTED)->values(), $interests->count()));
                User::whereKey($user->id)->toBase()->update(['last_interest_reminder_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $skipped++;
            }
        }

        $this->info(($dryRun ? 'Would send' : 'Sent') . " {$sent} interest reminder(s); skipped {$skipped}; deferred {$deferred} (daily cap {$cap}).");

        return self::SUCCESS;
    }
}
