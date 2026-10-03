<?php

namespace App\Console\Commands;

use App\Mail\PaymentReminderMail;
use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;

/**
 * "Complete your payment" — for members who started a payment 2 to 72
 * hours ago and didn't finish it (the Subscription row is still 'pending')
 * and haven't paid around then. One email per member per week; follows their
 * "Promotions" email setting.
 *
 * Settings: payment_reminders_enabled (default 1), payment_reminders_run_cap (default 20 per run).
 */
#[Signature('engagement:send-payment-reminders {--dry-run : List who would get a reminder without sending}')]
#[Description('Remind members who started a payment and did not finish it')]
class SendPaymentReminders extends Command
{
    public const MIN_AGE_HOURS = 2;
    public const MAX_AGE_HOURS = 72;
    public const REPEAT_AFTER_DAYS = 7;

    public function handle(): int
    {
        if (SiteSetting::getValue('payment_reminders_enabled', '1') !== '1') {
            $this->warn('Payment reminders are switched off (payment_reminders_enabled).');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $cap = max(1, (int) SiteSetting::getValue('payment_reminders_run_cap', '20'));

        // Latest unfinished payment per member
        $pending = Subscription::query()
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [now()->subHours(self::MAX_AGE_HOURS), now()->subHours(self::MIN_AGE_HOURS)])
            ->with('user')
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id');

        $sent = 0;
        $skipped = 0;

        foreach ($pending as $subscription) {
            $user = $subscription->user;

            $paidSince = $user && Subscription::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', $subscription->created_at->copy()->subDays(3))
                ->exists();

            if (! $user || $paidSince || blank($user->email) || $user->staff_role_id !== null
                || $user->blockedStatus() !== null || ! $user->wantsNotification('email_promotions')
                || ($user->last_payment_reminder_at && $user->last_payment_reminder_at->gt(now()->subDays(self::REPEAT_AFTER_DAYS)))) {
                $skipped++;
                continue;
            }

            if ($sent >= $cap) {
                break;
            }

            if ($dryRun) {
                $this->line("Would remind {$user->email}: {$subscription->plan_name}");
                $sent++;
                continue;
            }

            if ($sent > 0) {
                Sleep::for(2)->seconds();
            }

            try {
                Mail::to($user->email)->send(new PaymentReminderMail($user, (string) ($subscription->plan_name ?: 'premium')));
                User::whereKey($user->id)->toBase()->update(['last_payment_reminder_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $skipped++;
            }
        }

        $this->info(($dryRun ? 'Would send' : 'Sent') . " {$sent} payment reminder(s); skipped {$skipped}.");

        return self::SUCCESS;
    }
}
