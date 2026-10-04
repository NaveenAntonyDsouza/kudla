<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\EmailSuppression;
use Illuminate\Console\Command;

/**
 * Stop (or resume) emailing addresses that bounced permanently.
 *
 *   php artisan email:undeliverable mark a@b.com c@d.com --reason="550 5.1.1 does not exist"
 *   php artisan email:undeliverable clear a@b.com
 *   php artisan email:undeliverable list
 */
class MarkEmailUndeliverable extends Command
{
    protected $signature = 'email:undeliverable
        {action : mark | clear | list}
        {emails?* : member email addresses}
        {--reason= : bounce reason, for mark}';

    protected $description = 'Stop or resume emailing addresses that bounced permanently';

    public function handle(): int
    {
        $action = $this->argument('action');

        if ($action === 'list') {
            $rows = User::whereNotNull('email_undeliverable_at')->with('profile:id,user_id,matri_id')
                ->orderBy('email_undeliverable_at')->get()
                ->map(fn (User $u) => [
                    $u->profile?->matri_id ?? '-', $u->email, $u->email_undeliverable_reason,
                    $u->email_undeliverable_at?->format('Y-m-d'),
                    strcasecmp((string) $u->email, (string) $u->email_undeliverable_address) === 0 ? 'stopped' : 'email changed, sending again',
                ]);
            $this->table(['Matri ID', 'Email', 'Reason', 'Since', 'Status'], $rows);

            return self::SUCCESS;
        }

        if (! in_array($action, ['mark', 'clear'], true) || $this->argument('emails') === []) {
            $this->error('Use: mark|clear <emails...>, or list');

            return self::INVALID;
        }

        $reason = (string) ($this->option('reason') ?: 'Bounced: address does not exist');
        foreach ($this->argument('emails') as $email) {
            $done = $action === 'mark' ? EmailSuppression::mark($email, $reason) : EmailSuppression::clear($email);
            $this->line(($done ? '  ok   ' : '  skip ').$email.($done ? '' : ' (no matching member)'));
        }

        return self::SUCCESS;
    }
}
