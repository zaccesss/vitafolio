<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** every support email: the confirmation, a staff reply to the person and the alerts to admins */
class TicketUpdate extends Mailable
{
    /** @param  string  $event  opened or reply to the person; new or person-reply to admins */
    public function __construct(public SupportTicket $ticket, public string $event, public ?string $link) {}

    public function envelope(): Envelope
    {
        $ref = $this->ticket->reference();
        $subject = match ($this->event) {
            'opened' => __('We have your support request :ref', ['ref' => $ref]),
            'new' => __('New support ticket :ref: :subject', ['ref' => $ref, 'subject' => $this->ticket->subject]),
            default => __('New reply on :ref: :subject', ['ref' => $ref, 'subject' => $this->ticket->subject]),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.ticket-update');
    }
}
