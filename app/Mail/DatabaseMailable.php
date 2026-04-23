<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class DatabaseMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The template slug to look up in the database.
     */
    protected string $templateSlug;

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

    public function content(): Content
    {
        $template = EmailTemplate::findBySlug($this->templateSlug);
        $themeVars = $this->themeVariables(); // brand colors + logo for the wrapper

        if ($template) {
            $rendered = $template->render($this->buildVariables());

            return new Content(
                view: 'emails.database-template',
                with: array_merge(['body' => $rendered['body']], $themeVars),
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
