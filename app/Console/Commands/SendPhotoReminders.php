<?php

namespace App\Console\Commands;

use App\Mail\PhotoReminderMail;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

#[Signature('members:remind-photo
    {--dry-run : List who would get the reminder; send nothing}
    {--also=* : Matri IDs to include even if they would otherwise be skipped for being too new}
    {--delay=5 : Seconds to wait between emails (protects the mailbox sending limit)}
    {--limit=0 : Send to at most this many (0 = everyone eligible)}')]
#[Description('Email members who finished registering but have no photo (template "photo-reminder"). Run by hand — not scheduled.')]
class SendPhotoReminders extends Command
{
    /** Never remind the same member twice within this many days. */
    public const REPEAT_AFTER_DAYS = 30;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $also = array_map('strtoupper', array_filter((array) $this->option('also')));
        $limit = max(0, (int) $this->option('limit'));

        $recipients = User::query()
            ->whereNull('staff_role_id')
            ->where(fn ($q) => $q->where('role', '!=', 'admin')->orWhereNull('role'))
            ->where('is_active', true)
            ->where('email', 'like', '%@%.%')
            ->whereHas('profile', fn ($q) => $q->where('onboarding_completed', true)
                // No photo at all — an approved OR a waiting-for-approval one counts as having one
                ->whereDoesntHave('profilePhotos', fn ($p) => $p->whereIn('approval_status', ['approved', 'pending'])))
            // Not someone who joined in the last day (they may be adding one now)
            ->where(fn ($q) => $q->where('created_at', '<', now()->subDay())
                ->orWhereHas('profile', fn ($p) => $p->whereIn('matri_id', $also ?: ['']))
            )
            ->with('profile')
            ->orderBy('id')
            ->get()
            ->filter(fn (User $u) => $u->blockedStatus() === null
                && $u->wantsNotification('email_reengagement')
                && ($u->last_photo_reminder_at === null || $u->last_photo_reminder_at->lt(now()->subDays(self::REPEAT_AFTER_DAYS))))
            ->values();

        if ($limit > 0) {
            $recipients = $recipients->take($limit)->values();
        }

        $this->table(['Matri ID', 'Name', 'Email'], $recipients->map(fn (User $u) => [
            $u->profile->matri_id, $u->name, $this->mask($u->email),
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
                Mail::to($user->email)->send(new PhotoReminderMail($user));
                User::whereKey($user->id)->toBase()->update(['last_photo_reminder_at' => now()]);
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
                'action' => 'photo_reminders_sent',
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


    /** a***@gmail.com — enough to recognise, without printing addresses in full. */
    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2) . '***@' . $domain;
    }
}
