<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** tells an account holder that something about their sign-in changed, so a takeover cannot stay silent */
class SecurityNotice extends Mailable
{
    public function __construct(public string $headline, public string $detail) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->headline.' on your '.config('app.name').' account');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.security-notice');
    }
}
