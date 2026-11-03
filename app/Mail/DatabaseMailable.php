<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Support\EmailLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

abstract class DatabaseMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The template slug to look up in the database.
     */
    protected string $templateSlug;

    /**
     * The member's email preference this email belongs to (e.g.
     * 'email_weekly_matches'), for the one-tap unsubscribe header. Null for
     * service emails that can't be turned off: codes, approvals, payments,
     * security alerts.
     */
    protected ?string $unsubscribePreference = null;

    /**
     * Links back to the site sign the recipient member in (EmailSignInController).
     * Off for password / account-security emails; only ever for members, never staff.
     */
    protected bool $signInLinks = true;

    public const SIGN_IN_LINK_DAYS = 7;

    /** Seconds between one try at sending and the next (see sendWithRetries). */
    private const RETRY_PAUSE_SECONDS = 5;

    /**
     * Once this many emails in a row have failed despite their retries, the
     * mail server is down rather than having a moment: later sends skip the
     * retries until one gets through, so a bulk run (weekly matches,
     * re-engagement) is not stretched by ten seconds an email.
     */
    private const STOP_RETRYING_AFTER = 3;

    /** Emails in a row (this process) that failed on a passing-type fault even after retrying. */
    private static int $failuresInARow = 0;

    /** Links that must never sign anyone in. */
    private const NO_SIGN_IN_PATHS = [
        '/unsubscribe', '/resubscribe', '/confirm-email', '/still-looking', '/go/',
        '/forgot-password', '/reset-password', '/password', '/register', '/console',
    ];

    /**
     * Variables to substitute in the template.
     * Subclasses must implement this to provide their data.
     */
    abstract protected function templateVariables(): array;

    /**
     * Fallback Blade view if no DB template exists.
     * Subclasses can override this.
     */
    protected function fallbackView(): ?string
    {
        return null;
    }

    /**
     * Fallback subject if no DB template exists.
     */
    protected function fallbackSubject(): string
    {
        return config('app.name') . ' Notification';
    }

    /**
     * Fallback data for the Blade view.
     * Subclasses can override to pass data to the fallback Blade view.
     */
    protected function fallbackData(): array
    {
        return [];
    }

    public function envelope(): Envelope
    {
        $template = EmailTemplate::findBySlug($this->templateSlug);

        if ($template) {
            $rendered = $template->render($this->buildVariables());
            return new Envelope(subject: $rendered['subject']);
        }

        return new Envelope(subject: $this->fallbackSubject());
    }

    /**
     * An admin switching a template to Inactive (Email Templates → Active)
     * stops that email entirely. Every send path — immediate, queued (the
     * queue worker calls this too), and each subclass — goes through here.
     */
    public function send($mailer)
    {
        if (EmailTemplate::isDisabled($this->templateSlug)) {
            return null;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            self::paceBulkSending();
        }

        return $this->sendWithRetries(fn () => parent::send($mailer));
    }

    /**
     * A send that trips over a passing fault — the mailbox timed out, the
     * connection dropped — is tried again instead of being lost. 26 emails
     * went that way on kudla in the week of 27 Oct 2026 and nothing picked
     * them up. `mail_retry_attempts` (default 2, 0 = off) sets how many
     * extra tries; each one hangs up first, so the send opens a fresh
     * connection rather than reusing the one that just failed.
     *
     * Only the last failure is written to Email Activity, so the failed
     * count there stays "emails that never arrived". After STOP_RETRYING_AFTER
     * emails in a row have failed anyway, sends fail at once until one gets
     * through again.
     */
    protected function sendWithRetries(callable $send)
    {
        $left = self::$failuresInARow >= self::STOP_RETRYING_AFTER ? 0 : self::retryAttempts();
        $tries = 1;

        while (true) {
            try {
                $result = $send();
                self::$failuresInARow = 0;

                return $result;
            } catch (\Throwable $e) {
                $passing = self::worthRetrying($e);
                if ($left < 1 || ! $passing) {
                    if ($passing) {
                        self::$failuresInARow++;   // a refused address says nothing about the server
                    }
                    // For admin → Reports → Email Activity; the caller still sees the error
                    EmailLogger::failed($this->templateSlug, $this->to, $e, $tries);
                    throw $e;
                }

                $left--;
                $tries++;
                self::closeSmtpConnection();
                if (! app()->runningUnitTests()) {
                    sleep(self::RETRY_PAUSE_SECONDS);
                }
            }
        }
    }

    private static function retryAttempts(): int
    {
        return max(0, (int) SiteSetting::getValue('mail_retry_attempts', '2'));
    }

    /** For tests: the failure count lives as long as the process, so each test starts from none. */
    public static function forgetFailures(): void
    {
        self::$failuresInARow = 0;
    }

    /**
     * Worth another try: a timeout, a dropped connection, or any other 4xx
     * answer. A 5xx is the mailbox's final word, and a quota refusal is a
     * limit that the next few seconds cannot change — both are left alone.
     *
     * The code is read from the mailbox's own answer, never from any other
     * number in the message: a host written "smtp.example.com:587" must not
     * be mistaken for a 5xx refusal.
     */
    private static function worthRetrying(\Throwable $e): bool
    {
        $message = $e->getMessage();

        if (preg_match('/rate.?limit|quota|too much mail|too many/i', $message)) {
            return false;
        }

        $code = self::smtpCode($message);

        // No code to read: the connection dropped or never opened. Worth another try.
        return $code === null
            ? $e instanceof \Symfony\Component\Mailer\Exception\TransportExceptionInterface
            : $code >= 400 && $code < 500;
    }

    /** The status the mailbox answered with, if the message carries one. */
    private static function smtpCode(string $message): ?int
    {
        $patterns = [
            '/got code "(\d{3})"/',           // Symfony: Expected response code "250" but got code "421"
            '/(?:^|")(\d{3}) \d+\.\d+\.\d+/', // a raw SMTP line: 451 4.7.1 ...
            '/^(\d{3})\b/',                   // a bare leading code
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $m)) {
                return (int) $m[1];
            }
        }

        return null;
    }

    /**
     * Scheduled runs (weekly matches, reminders, digests, re-engagement) can
     * overlap; each paces itself, but together they could still outrun the
     * mailbox's limit ("450 too much mail", Oct 2026). One shared gap between
     * any two emails sent from scheduled/queued work: `mail_min_gap_seconds`
     * (default 2, 0 = off). Web requests (one email each) are never slowed.
     */
    private static function paceBulkSending(): void
    {
        $gap = (float) SiteSetting::getValue('mail_min_gap_seconds', '2');
        if ($gap <= 0) {
            return;
        }

        try {
            Cache::lock('mail-pace', 30)->block(60, function () use ($gap) {
                $wait = (float) Cache::get('mail-pace-last', 0) + $gap - microtime(true);
                if ($wait > 0) {
                    // Hostinger drops an SMTP connection idle for ~10 s, and the next
                    // email on it failed "421 timeout exceeded" (kudla, 26 a week at
                    // a 20 s gap, Oct 2026): hang up first, the send reconnects.
                    if ($wait >= 5) {
                        self::closeSmtpConnection();
                    }
                    usleep((int) (min($wait, $gap) * 1_000_000));
                }
                Cache::put('mail-pace-last', microtime(true), 3600);
            });
        } catch (\Throwable) {
            // Never block an email because pacing could not get its lock.
        }
    }

    private static function closeSmtpConnection(): void
    {
        try {
            $transport = app('mail.manager')->mailer()->getSymfonyTransport();
            if ($transport instanceof \Symfony\Component\Mailer\Transport\Smtp\SmtpTransport) {
                $transport->stop();
            }
        } catch (\Throwable) {
            // the send will still try; at worst it fails as before
        }
    }

    /**
     * One-tap unsubscribe (List-Unsubscribe + List-Unsubscribe-Post, RFC 8058)
     * for emails that belong to a member's email preference, plus a
     * Feedback-ID naming the email type for Gmail's sender statistics.
     */
    public function headers(): Headers
    {
        $text = ['Feedback-ID' => $this->templateSlug . ':' . (self::siteHost() ?: 'site')];

        $url = $this->oneClickUnsubscribeUrl();
        if ($url !== null) {
            $text['List-Unsubscribe'] = '<' . $url . '>';
            $text['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        return new Headers(text: $text);
    }

    /** The member receiving this email, for their unsubscribe link. */
    protected function recipient(): ?User
    {
        return null;
    }

    /** The member links in this email may sign in, if any: members only, never staff or admin. */
    private function signInMember(): ?User
    {
        $user = $this->signInLinks ? $this->recipient() : null;

        return $user && $user->id && $user->staff_role_id === null && $user->role !== 'admin' ? $user : null;
    }

    protected function oneClickUnsubscribeUrl(): ?string
    {
        if ($this->unsubscribePreference === null) {
            return null;
        }

        try {
            $user = $this->recipient();
        } catch (\Throwable) {
            return null;
        }

        return $user ? $user->unsubscribeUrl($this->unsubscribePreference) : null;
    }

    public function content(): Content
    {
        $template = EmailTemplate::findBySlug($this->templateSlug);
        $themeVars = $this->themeVariables(); // brand colors + logo for the wrapper

        if ($template) {
            $rendered = $template->render($this->buildVariables());

            return new Content(
                view: 'emails.database-template',
                with: array_merge([
                    'body' => $this->tagLinks($rendered['body']),
                    'preheader' => $rendered['preheader'] ?? '',
                ], $themeVars),
            );
        }

        // Fall back to Blade view
        $fallbackView = $this->fallbackView();
        if ($fallbackView) {
            return new Content(
                markdown: $fallbackView,
                with: array_merge($this->fallbackData(), $themeVars),
            );
        }

        // Last resort: render variables as simple message
        return new Content(
            view: 'emails.database-template',
            with: array_merge(
                ['body' => '<p>You have a new notification from ' . config('app.name') . '.</p>'],
                $themeVars
            ),
        );
    }

    /**
     * Campaign tags on every link back to this site, so GA4 shows which email
     * brought a member back (utm_campaign = the template's slug). Signed links,
     * such as unsubscribe, are left alone: changing them breaks the signature.
     */
    protected function tagLinks(string $html): string
    {
        $host = self::siteHost();
        if ($host === null) {
            return $html;
        }

        return preg_replace_callback('/href=(["\'])(.*?)\1/i', function (array $m) use ($host) {
            $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5);
            $parts = parse_url($url);
            if (! is_array($parts) || ! isset($parts['host'])
                || strcasecmp(preg_replace('/^www\./i', '', $parts['host']), $host) !== 0) {
                return $m[0];
            }

            parse_str($parts['query'] ?? '', $query);
            if (isset($query['signature']) || isset($query['utm_campaign'])) {
                return $m[0];
            }

            $query += ['utm_source' => 'email', 'utm_medium' => 'email', 'utm_campaign' => $this->templateSlug];
            $path = $parts['path'] ?? '/';
            $tagged = ($parts['scheme'] ?? 'https') . '://' . $parts['host']
                . (isset($parts['port']) ? ':' . $parts['port'] : '')
                . $path
                . '?' . http_build_query($query)
                . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

            // One tap from the email signs the member in (owner's choice, 5 Oct 2026:
            // 37 of 43 members who tapped an email link gave up at the login page)
            $member = $this->signInMember();
            if ($member && ! Str::startsWith($path, self::NO_SIGN_IN_PATHS)) {
                $target = ($path === '/login' ? '/' : $path) . '?' . http_build_query($query)
                    . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
                $tagged = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'email.sign-in', now()->addDays(self::SIGN_IN_LINK_DAYS), ['user' => $member->id, 'to' => $target]
                );
            }

            return 'href=' . $m[1] . e($tagged) . $m[1];
        }, $html) ?? $html;
    }

    /** This site's host without "www.", from APP_URL. */
    protected static function siteHost(): ?string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $host ? preg_replace('/^www\./i', '', $host) : null;
    }

    /**
     * Build the full variable map with common defaults + theme variables.
     * Templates can use {{PRIMARY_COLOR}}, {{LOGO_URL}}, etc. in their HTML.
     */
    protected function buildVariables(): array
    {
        return array_merge([
            'SITE_NAME' => SiteSetting::getValue('site_name', config('app.name')),
            'SITE_URL' => config('app.url'),
            'LOGIN_URL' => url('/login'),
            // Theme variables (Phase 2.6D) — use these in email template HTML
            'PRIMARY_COLOR' => $this->getPrimaryColor(),
            'PRIMARY_HOVER' => $this->getPrimaryHover(),
            'PRIMARY_LIGHT' => $this->getPrimaryLight(),
            'SECONDARY_COLOR' => $this->getSecondaryColor(),
            'LOGO_URL' => $this->getLogoUrl(),
            'TAGLINE' => SiteSetting::getValue('tagline', ''),
        ], $this->templateVariables());
    }

    /**
     * Theme variables for the email wrapper template.
     */
    protected function themeVariables(): array
    {
        return [
            'primaryColor' => $this->getPrimaryColor(),
            'primaryHover' => $this->getPrimaryHover(),
            'primaryLight' => $this->getPrimaryLight(),
            'secondaryColor' => $this->getSecondaryColor(),
            'logoUrl' => $this->getLogoUrl(),
            'siteName' => SiteSetting::getValue('site_name', config('app.name')),
            'tagline' => SiteSetting::getValue('tagline', ''),
        ];
    }

    protected function getPrimaryColor(): string
    {
        return ThemeSetting::first()?->primary_color ?? '#8B1D91';
    }

    protected function getPrimaryHover(): string
    {
        return ThemeSetting::first()?->primary_hover ?? '#6B1571';
    }

    protected function getPrimaryLight(): string
    {
        return ThemeSetting::first()?->primary_light ?? '#F3E8F7';
    }

    protected function getSecondaryColor(): string
    {
        return ThemeSetting::first()?->secondary_color ?? '#00BCD4';
    }

    protected function getLogoUrl(): string
    {
        $logo = ThemeSetting::first()?->logo_url ?? '';
        if (!$logo) return '';
        // If it's a relative path, make it absolute for emails
        return str_starts_with($logo, 'http') ? $logo : rtrim(config('app.url'), '/') . $logo;
    }
}
