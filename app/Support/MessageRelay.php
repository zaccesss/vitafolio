<?php

namespace App\Support;

use App\Mail\VisitorMessage;
use App\Models\Cv;
use App\Models\PendingMessage;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** delivers contact and cv messages, holding any that fail so a visitor's words are never lost */
class MessageRelay
{
    /** sends now; on failure the error is reported and the message is kept for the nightly retry */
    public static function send(array $data, ?Cv $cv = null): void
    {
        try {
            self::deliver($data, $cv);
        } catch (Throwable $e) {
            report($e);
            PendingMessage::create(['cv_id' => $cv?->id, 'payload' => $data]);
        }
    }

    /** one retry of every held message; returns how many went out */
    public static function retry(): int
    {
        $sent = 0;
        foreach (PendingMessage::with('cv.user')->oldest()->get() as $pending) {
            // a site message waits while no inbox is configured, since there is nowhere to deliver it
            if ($pending->cv_id === null && blank(config('vitafolio.contact_email'))) {
                continue;
            }
            try {
                self::deliver($pending->payload, $pending->cv);
                $pending->delete();
                $sent++;
            } catch (Throwable $e) {
                report($e);
                $pending->increment('attempts');
            }
        }

        return $sent;
    }

    /** @throws Throwable when the mail service refuses the message */
    private static function deliver(array $data, ?Cv $cv): void
    {
        if ($cv === null) {
            // the site's own inbox reads english, whatever language the visitor used
            Mail::to(config('vitafolio.contact_email'))->locale(Locales::DEFAULT)->send(new VisitorMessage($data, ':app enquiry'));

            return;
        }
        // addressed to the account, so it arrives in the owner's language rather than the visitor's
        Mail::to($cv->user)->send(new VisitorMessage($data, 'Message about your CV on :app', route('cv.show', $cv)));
    }
}
