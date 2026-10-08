<?php

namespace App\Console\Commands;

use App\Models\SupportAttachment;
use App\Models\SupportTicket;
use App\Support\Cloudinary;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('vitafolio:tidy')]
#[Description('Clear expired sessions, cache rows, reset tokens, released handles and old view stats')]
class Tidy extends Command
{
    /** view stats older than this are dropped; the owner's charts only ever show the last year */
    private const KEEP_VIEWS_MONTHS = 13;

    public function handle(): int
    {
        $counts = [
            'released handles' => DB::table('handle_history')->where('released_at', '<', now())->delete(),
            'old view stats' => DB::table('cv_views')->where('viewed_on', '<', now()->subMonths(self::KEEP_VIEWS_MONTHS)->toDateString())->delete(),
            'expired sessions' => DB::table(config('session.table', 'sessions'))
                ->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())->delete(),
            'expired cache rows' => DB::table('cache')->where('expiration', '<', time())->delete()
                + DB::table('cache_locks')->where('expiration', '<', time())->delete(),
            // a listing is dropped once it closes or, without a closing date, a month after posting
            'old job listings' => DB::table('job_listings')
                ->where(fn ($q) => $q->where('closes_at', '<', now())->orWhere(fn ($q) => $q->where('source', '!=', 'employer')->where('posted_at', '<', now()->subDays(30)))->orWhere(fn ($q) => $q->whereNull('posted_at')->where('updated_at', '<', now()->subDays(30))))->delete(),
            // the employer feed resends every open listing daily, so one it has not sent for three days has closed
            'unlisted employer jobs' => DB::table('job_listings')->where('source', 'employer')->where('last_seen_at', '<', now()->subDays(3))->delete(),
            // a resolved ticket nobody has added to for two weeks is closed; closed tickets go after a year
            'closed tickets' => DB::table('support_tickets')->where('status', 'resolved')->where('last_activity_at', '<', now()->subDays(14))
                ->update(['status' => 'closed', 'updated_at' => now()]),
            'old tickets' => $this->deleteOldTickets(),
            // click counts are only charted for 30 days, so a year is plenty
            'old apply clicks' => DB::table('job_clicks')->where('clicked_on', '<', now()->subYear()->toDateString())->delete(),
            'expired reset tokens' => DB::table('password_reset_tokens')
                ->where('created_at', '<', now()->subMinutes((int) config('auth.passwords.users.expire', 60)))->delete(),
        ];
        foreach ($counts as $what => $count) {
            $this->line(str_pad($what, 22).$count);
        }

        return self::SUCCESS;
    }

    /** closed tickets older than a year, with their screenshots removed from the image host first */
    private function deleteOldTickets(): int
    {
        $old = SupportTicket::where('status', 'closed')->where('last_activity_at', '<', now()->subYear())->pluck('id');
        if ($old->isEmpty()) {
            return 0;
        }
        if (Cloudinary::enabled()) {
            SupportAttachment::whereHas('message', fn ($q) => $q->whereIn('support_ticket_id', $old))
                ->pluck('public_id')->each(fn (string $id) => rescue(fn () => Cloudinary::destroy($id, 'file')));
        }

        return SupportTicket::whereIn('id', $old)->delete();
    }
}
