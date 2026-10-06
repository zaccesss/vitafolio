<?php

namespace App\Mail;

use App\Models\Endorsement;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** tells a cv's owner that an endorsement waits for them, since nothing shows until they approve it */
class EndorsementReceived extends Mailable
{
    public function __construct(public Endorsement $endorsement) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'A new endorsement is waiting for your approval on '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(text: 'emails.endorsement-received');
    }
}
