<?php

namespace App\Mail;

use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterestDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Interest $interest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Interest Update - ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.interest-declined',
            with: [
                'declinerMatriId' => $this->interest->receiverProfile->matri_id,
                'senderName' => $this->interest->senderProfile->full_name,
                'url' => route('interests.inbox'),
                'siteName' => config('app.name'),
            ],
        );
    }
}
