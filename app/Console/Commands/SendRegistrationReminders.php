<?php

namespace App\Console\Commands;

use App\Mail\RegistrationReminderMail;
use App\Models\AdminActivityLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

#[Signature('members:remind-incomplete
    {--dry-run : List who would get the reminder; send nothing}
    {--also=* : Matri IDs to include even if they joined in the last 24 hours}
    {--delay=10 : Seconds to wait between emails (protects the mailbox sending limit)}
    {--scheduled : The daily run: only where registration_reminders_enabled = 1, at most registration_reminders_daily_cap emails}')]
#[Description('Email members who started registering but did not finish (template "registration-reminder"): at most 3 times, 1, 4 and 10 days apart.')]
class SendRegistrationReminders extends Command
{
    /**
     * Reminder n is due this many days after the previous one (the first:
     * after signing up). After the last one, never again.
     */
    public const GAPS_DAYS = [1 => 1, 2 => 4, 3 => 10];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $also = array_map('strtoupper', array_filter((array) $this->option('also')));
        $cap = PHP_INT_MAX;

        if ($this->option('scheduled')) {
            if (SiteSetting::getValue('registration_reminders_enabled', '0') !== '1') {
                $this->info('Registration reminders are off (registration_reminders_enabled).');

                return self::SUCCESS;
            }
            $cap = max(0, (int) SiteSetting::getValue('registration_reminders_daily_cap', '60'));
        }

        $recipients = User::query()
            ->whereNull('staff_role_id')
            ->where(fn ($q) => $q->where('role', '!=', 'admin')->orWhereNull('role'))
            ->where('is_active', true)
            ->where('email', 'like', '%@%.%')
            ->whereHas('profile', fn ($q) => $q->where('onboarding_completed', false))
            // Not someone who signed up in the last day (they may still be at it)
            ->where(fn ($q) => $q->where('created_at', '<', now()->subDay())
                ->orWhereHas('profile', fn ($p) => $p->whereIn('matri_id', $also ?: ['']))
            )
            ->with('profile')
            ->orderBy('id')
            ->get()
            ->filter(fn (User $u) => $u->blockedStatus() === null              // not deactivated / deleted / suspended
                && $u->wantsNotification('email_reengagement')                  // not unsubscribed
                && $this->due($u, in_array(strtoupper((string) $u->profile->matri_id), $also, true)))
            ->values()
            ->take($cap); // the rest are still due tomorrow

        $this->table(['Matri ID', 'Name', 'Stopped after step', 'Email'], $recipients->map(fn (User $u) => [
            $u->profile->matri_id, $u->name, $u->profile->onboarding_step_completed, $this->mask($u->email),
        ])->all());
        $this->info("Recipients: {$recipients->count()}");

        if ($dryRun) {
            $this->warn('DRY RUN — nothing sent.');
            return self::SUCCESS;
        }

        $delay = max(0, (int) $this->option('delay'));
        $sent = $failed = [];
        foreach ($recipients as $i => $user) {
            if ($i > 0 && $delay > 0) {
                sleep($delay);
            }
            try {
                Mail::to($user->email)->send(new RegistrationReminderMail($user));
                User::whereKey($user->id)->toBase()->update([
                    'last_registration_reminder_at' => now(),
                    'registration_reminders_sent' => (int) $user->registration_reminders_sent + 1,
                ]);
                $sent[] = $user->profile->matri_id;
                $this->line("sent   {$user->profile->matri_id}");
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $user->profile->matri_id;
                $this->error("FAILED {$user->profile->matri_id}: {$e->getMessage()}");
            }
        }

        try { // audit record — must never undo a run whose emails already went out
            AdminActivityLog::create([
                'admin_user_id' => DB::table('users')->where('role', 'admin')->orderBy('id')->value('id'),
                'action' => 'registration_reminders_sent',
                'model_type' => null,
                'model_id' => null,
                'changes' => ['sent' => $sent, 'failed' => $failed],
                'ip_address' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->info('Sent: ' . count($sent) . ', failed: ' . count($failed));

        return $failed ? self::FAILURE : self::SUCCESS;
    }


    /** Is this member's next reminder due? (--also skips the first day's wait.) */
    private function due(User $user, bool $named): bool
    {
        $next = (int) $user->registration_reminders_sent + 1;
        if (! isset(self::GAPS_DAYS[$next])) {
            return false; // had them all
        }
        if ($next === 1) {
            return $named || $user->created_at?->lt(now()->subDays(self::GAPS_DAYS[1]));
        }

        $last = $user->last_registration_reminder_at;

        return $last === null || $last->lte(now()->subDays(self::GAPS_DAYS[$next]));
    }

    /** a***@gmail.com — enough to recognise, without printing addresses in full. */
    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2) . '***@' . $domain;
    }
}
