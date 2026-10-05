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

        try {
            return parent::send($mailer);
        } catch (\Throwable $e) {
            // For admin → Reports → Email Activity; the caller still sees the error
            EmailLogger::failed($this->templateSlug, $this->to, $e);
            throw $e;
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
