<?php

namespace App\Mail;

use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterestReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Interest $interest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Interest Received - ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.interest-received',
            with: [
                'senderMatriId' => $this->interest->senderProfile->matri_id,
                'receiverName' => $this->interest->receiverProfile->full_name,
                'url' => route('interests.show', $this->interest),
                'siteName' => config('app.name'),
            ],
        );
    }
}
