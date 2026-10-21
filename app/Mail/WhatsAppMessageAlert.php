<?php

namespace App\Mail;

use App\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells staff a member wrote to the WhatsApp API number (Oct 2026), so nobody
 * has to keep the inbox open. Plain text, staff-only.
 */
class WhatsAppMessageAlert extends Mailable
{
    use Queueable;

    public function __construct(
        public WhatsAppMessage $whatsappMessage,
        public string $sender,
        public string $preview,
        public string $inboxUrl,
        public int $quietMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'WhatsApp: new message from '.$this->sender);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.whatsapp-message-alert');
    }
}
