<?php

namespace App\Mail;

use App\Models\Interest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterestAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Interest $interest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Interest Accepted! - ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.interest-accepted',
            with: [
                'accepterMatriId' => $this->interest->receiverProfile->matri_id,
                'senderName' => $this->interest->senderProfile->full_name,
                'url' => route('interests.show', $this->interest),
                'siteName' => config('app.name'),
            ],
        );
    }
}
