<?php

namespace App\Mail;

use App\Models\EmailTemplate;
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

        if ($template) {
            $rendered = $template->render($this->buildVariables());

            return new Content(
                view: 'emails.database-template',
                with: ['body' => $rendered['body']],
            );
        }

        // Fall back to Blade view
        $fallbackView = $this->fallbackView();
        if ($fallbackView) {
            return new Content(
                markdown: $fallbackView,
                with: $this->fallbackData(),
            );
        }

        // Last resort: render variables as simple message
        return new Content(
            view: 'emails.database-template',
            with: ['body' => '<p>You have a new notification from ' . config('app.name') . '.</p>'],
        );
    }

    /**
     * Build the full variable map with common defaults.
     */
    private function buildVariables(): array
    {
        return array_merge([
            'SITE_NAME' => config('app.name'),
            'SITE_URL' => config('app.url'),
            'LOGIN_URL' => url('/login'),
        ], $this->templateVariables());
    }
}
