<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Members whose email address bounced permanently ("address does not exist",
 * "account disabled") are not emailed again: every further send to a dead
 * address costs mailbox quota and sender reputation with Gmail/Yahoo.
 *
 * The mark stores the address that bounced. It applies only while the member's
 * email is still that address, so correcting the email (admin, member, or a
 * direct DB fix) lifts it with no extra step.
 *
 * Marked from the bounce notices in the site mailbox:
 *   php artisan email:undeliverable mark a@b.com --reason="550 5.1.1 does not exist"
 * Listed for staff in admin → Reports → Email Activity, so they can phone them.
 */
final class EmailSuppression
{
    /** Listener for Laravel's MessageSending event: false stops the send. Never throws. */
    public static function check(MessageSending $event): ?bool
    {
        try {
            $to = collect($event->message->getTo())->map(fn ($a) => Str::lower($a->getAddress()))->all();
            if ($to === []) {
                return null;
            }

            $dead = User::query()->whereNotNull('email_undeliverable_at')
                ->whereIn(DB::raw('LOWER(email_undeliverable_address)'), $to)
                ->whereRaw('LOWER(email) = LOWER(email_undeliverable_address)')
                ->first(['id', 'email']);
            if (! $dead) {
                return null;
            }

            EmailLog::create([
                'event' => 'suppressed',
                'email_type' => EmailLogger::typeOf($event->message, $event->data),
                'subject' => Str::limit((string) $event->message->getSubject(), 250, ''),
                'recipient' => $dead->email,
                'user_id' => $dead->id,
            ]);

            return false;
        } catch (\Throwable) {
            return null; // never block an email because this check failed
        }
    }

    /** Mark a member's current address undeliverable. Returns false if no member has it. */
    public static function mark(string $email, string $reason, ?int $adminId = null): bool
    {
        $user = User::whereRaw('LOWER(email) = ?', [Str::lower(trim($email))])->first(['id', 'email']);
        if (! $user) {
            return false;
        }

        User::whereKey($user->id)->toBase()->update([
            'email_undeliverable_at' => now(),
            'email_undeliverable_address' => $user->email,
            'email_undeliverable_reason' => Str::limit($reason, 190, ''),
        ]);
        self::audit('email_marked_undeliverable', $user, $reason, $adminId);

        return true;
    }

    public static function clear(string $email, ?int $adminId = null): bool
    {
        $user = User::whereRaw('LOWER(email) = ?', [Str::lower(trim($email))])->whereNotNull('email_undeliverable_at')->first(['id', 'email']);
        if (! $user) {
            return false;
        }

        User::whereKey($user->id)->toBase()->update([
            'email_undeliverable_at' => null, 'email_undeliverable_address' => null, 'email_undeliverable_reason' => null,
        ]);
        self::audit('email_undeliverable_cleared', $user, null, $adminId);

        return true;
    }

    private static function audit(string $action, User $user, ?string $reason, ?int $adminId): void
    {
        try {
            AdminActivityLog::create([
                'admin_user_id' => $adminId,
                'action' => $action,
                'model_type' => User::class,
                'model_id' => $user->id,
                'changes' => ['email' => $user->email, 'reason' => $reason],
            ]);
        } catch (\Throwable) {
            // the mark itself matters more than its audit row
        }
    }
}
