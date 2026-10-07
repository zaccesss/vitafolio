<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** tells an account holder that something about their sign-in changed, so a takeover cannot stay silent */
class SecurityNotice extends Mailable
{
    /** headline and detail are english keys, translated when the email is built in its reader's language */
    public function __construct(public string $headline, public string $detail, public array $replace = []) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __(':headline on your :app account', ['headline' => __($this->headline, $this->replace), 'app' => config('app.name')]));
    }

    public function content(): Content
    {
        return new Content(text: 'emails.security-notice');
    }
}
