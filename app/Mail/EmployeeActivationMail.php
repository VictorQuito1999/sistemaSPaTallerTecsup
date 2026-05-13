<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $activationUrl  URL firmada absoluta generada con URL::temporarySignedRoute (basada en APP_URL).
     */
    public function __construct(
        public string $activationUrl,
        public string $employeeDisplayName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Pet Spa — Activa tu cuenta'),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mails.employee-activation-html',
            text: 'mails.employee-activation-plain',
        );
    }

    /** @return array<int, mixed> */
    public function attachments(): array
    {
        return [];
    }
}
