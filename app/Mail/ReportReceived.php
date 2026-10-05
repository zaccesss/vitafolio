<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** tells admins a cv was reported, so nothing waits unseen in the moderation queue */
class ReportReceived extends Mailable
{
    public function __construct(public Report $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'A CV was reported on '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(text: 'emails.report-received');
    }
}
