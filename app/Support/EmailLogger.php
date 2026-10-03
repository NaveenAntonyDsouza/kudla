<?php

namespace App\Support;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

/**
 * Records email activity for admin → Reports → Email Activity: every email
 * that left the site, every failed send attempt, every unsubscribe.
 *
 * Never throws: logging must not be able to break sending an email.
 */
final class EmailLogger
{
    /** Listener for Laravel's MessageSent event (registered in AppServiceProvider). */
    public static function sent(MessageSent $event): void
    {
        self::write(function () use ($event) {
            $message = $event->message;

            EmailLog::create([
                'event' => 'sent',
                'email_type' => self::typeOf($message, $event->data),
                'subject' => Str::limit((string) $message->getSubject(), 250, ''),
                'recipient' => ($message->getTo()[0] ?? null)?->getAddress(),
            ]);
        });
    }

    /** A DatabaseMailable whose send threw (mailbox refused, quota hit, network). */
    public static function failed(string $type, array $recipients, \Throwable $e): void
    {
        self::write(fn () => EmailLog::create([
            'event' => 'failed',
            'email_type' => $type,
            'recipient' => $recipients[0]['address'] ?? null,
            'error' => Str::limit($e->getMessage(), 500),
        ]));
    }

    /** A member switched an email category off (link in an email or one-tap button). */
    public static function unsubscribed(User $user, string $preference): void
    {
        self::write(fn () => EmailLog::create([
            'event' => 'unsubscribed',
            'email_type' => $preference,
            'recipient' => $user->email,
            'user_id' => $user->id,
        ]));
    }

    /**
     * Which email this was: the template slug from our Feedback-ID header,
     * else the mailable or notification class, else a guess from the subject
     * (one-time codes and password emails are plain Mail::raw messages).
     */
    public static function typeOf(Email $message, array $data = []): string
    {
        $feedback = $message->getHeaders()->get('Feedback-ID')?->getBodyAsString();
        if ($feedback && str_contains($feedback, ':')) {
            return Str::before($feedback, ':');
        }

        $class = $data['__laravel_mailable'] ?? $data['__laravel_notification'] ?? null;
        if (is_string($class) && $class !== '') {
            return Str::kebab(preg_replace('/(Mail|Notification)$/', '', class_basename($class)));
        }

        $subject = (string) $message->getSubject();

        return match (true) {
            (bool) preg_match('/\b(otp|code|verif)/i', $subject) => 'verification-code',
            (bool) preg_match('/password/i', $subject) => 'password',
            (bool) preg_match('/\btest\b/i', $subject) => 'test',
            default => 'other',
        };
    }

    private static function write(callable $write): void
    {
        try {
            $write();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
