<?php
// app/Mail/VerifyEmailMail.php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PendingRegistration $pendingRegistration
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email Address - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.verify-email-text',
            with: [
                'userName' => $this->pendingRegistration->full_name,
                'code' => $this->pendingRegistration->code,
                'expiresInMinutes' => config('auth.verification.expire', 60),
            ],
        );
    }
}
