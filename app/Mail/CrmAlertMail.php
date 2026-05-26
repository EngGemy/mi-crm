<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CrmAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $alertTitle,
        public string $alertBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->alertTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.crm-alert', with: [
            'alertTitle' => $this->alertTitle,
            'alertBody' => $this->alertBody,
        ]);
    }
}
