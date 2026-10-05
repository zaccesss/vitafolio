<?php

namespace App\Console\Commands;

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
            'expired reset tokens' => DB::table('password_reset_tokens')
                ->where('created_at', '<', now()->subMinutes((int) config('auth.passwords.users.expire', 60)))->delete(),
        ];
        foreach ($counts as $what => $count) {
            $this->line(str_pad($what, 22).$count);
        }

        return self::SUCCESS;
    }
}
