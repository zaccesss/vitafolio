<?php

namespace App\Support;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewRecorder
{
    /** one view per visitor per cv per day, never counting the owner or obvious bots */
    public static function record(Cv $cv, Request $request, ?User $viewer): void
    {
        $agent = (string) $request->userAgent();
        if ($viewer?->id === $cv->user_id || $agent === '' || preg_match('/bot|crawl|spider|preview|monitor|uptime|curl|wget/i', $agent)) {
            return;
        }

        // a salted daily hash de-duplicates visits without storing anyone's address
        $visitor = hash('sha256', now()->toDateString().'|'.$request->ip().'|'.$agent.'|'.config('app.key'));
        $referrer = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST) ?: null;
        if ($referrer === $request->getHost()) {
            $referrer = null;
        }

        $inserted = DB::table('cv_views')->insertOrIgnore([
            'cv_id' => $cv->id,
            'viewed_on' => now()->toDateString(),
            'visitor_hash' => $visitor,
            'referrer_host' => $referrer ? substr($referrer, 0, 100) : null,
        ]);
        if ($inserted === 1) {
            $cv->increment('view_count');
        }
    }

    /** date => views for the last n days, including days with none */
    public static function series(Cv $cv, int $days = 30): array
    {
        $counts = DB::table('cv_views')->where('cv_id', $cv->id)
            ->where('viewed_on', '>', now()->subDays($days)->toDateString())
            ->groupBy('viewed_on')->selectRaw('viewed_on, COUNT(*) AS views')
            ->pluck('views', 'viewed_on');
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $series[$day] = (int) ($counts[$day] ?? 0);
        }

        return $series;
    }

    public static function referrers(Cv $cv, int $limit = 5): array
    {
        return DB::table('cv_views')->where('cv_id', $cv->id)->whereNotNull('referrer_host')
            ->groupBy('referrer_host')->selectRaw('referrer_host, COUNT(*) AS views')
            ->orderByDesc('views')->limit($limit)->get()->all();
    }
}
