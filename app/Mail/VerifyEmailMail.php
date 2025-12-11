<?php
// app/Mail/VerifyEmailMail.php

namespace App\Mail;

use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public EmailVerification $verification
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email Address - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        // Use text instead of view - no Blade template needed!
        return new Content(
            text: 'emails.verify-email-text',
            with: [
                'userName' => $this->user->full_name,
                'code' => $this->verification->code,
                'expiresInMinutes' => config('auth.verification.expire', 60),
            ],
        );
    }
}
