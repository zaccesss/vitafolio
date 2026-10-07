<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisitorMessage extends Mailable
{
    use Queueable, SerializesModels;

    /** the heading is an english key with an :app placeholder, translated in the reader's language */
    public function __construct(public array $data, public string $heading, public ?string $cvUrl = null) {}

    public function envelope(): Envelope
    {
        // line breaks are removed so a visitor's name can never add an extra mail header
        $name = str_replace(["\r", "\n"], ' ', $this->data['sender_name']);

        return new Envelope(
            replyTo: [new Address($this->data['sender_email'], $name)],
            subject: __(':heading from :name', ['heading' => __($this->heading, ['app' => config('app.name')]), 'name' => $name]),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.visitor-message');
    }
}
