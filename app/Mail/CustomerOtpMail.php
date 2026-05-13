<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerOtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $otpCode,
        public string $customerName = 'cliente',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu codigo OTP de Pet Spa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-otp-code',
            with: [
                'otpCode' => $this->otpCode,
                'customerName' => $this->customerName,
            ],
        );
    }
}
