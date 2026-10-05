<?php

namespace App\Support;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewRecorder
{
    /** what a cv count can be; a qr scan is a view that arrived through the printed code */
    public const KINDS = ['view' => 'Views', 'qr' => 'QR scans', 'pdf' => 'PDF downloads', 'file' => 'File opens'];

    /** one count per visitor per cv per kind per day, never counting the owner or obvious bots */
    public static function record(Cv $cv, Request $request, ?User $viewer, string $kind = 'view'): void
    {
        $visitor = self::visitor($request, $viewer, $cv->user_id);
        if ($visitor === null || ! array_key_exists($kind, self::KINDS)) {
            return;
        }
        $inserted = DB::table('cv_views')->insertOrIgnore([
            'cv_id' => $cv->id,
            'kind' => $kind,
            'viewed_on' => now()->toDateString(),
            'visitor_hash' => $visitor,
            'referrer_host' => self::referrer($request),
        ]);
        if ($inserted === 1 && in_array($kind, ['view', 'qr'], true)) {
            $cv->increment('view_count');
        }
    }

    public static function recordProfile(User $owner, Request $request, ?User $viewer): void
    {
        $visitor = self::visitor($request, $viewer, $owner->id);
        if ($visitor === null) {
            return;
        }
        DB::table('profile_views')->insertOrIgnore([
            'user_id' => $owner->id,
            'viewed_on' => now()->toDateString(),
            'visitor_hash' => $visitor,
            'referrer_host' => self::referrer($request),
        ]);
    }

    /** date => views for the last n days, including days with none */
    public static function series(Cv $cv, int $days = 30): array
    {
        $counts = DB::table('cv_views')->where('cv_id', $cv->id)->whereIn('kind', ['view', 'qr'])
            ->where('viewed_on', '>', now()->subDays($days)->toDateString())
            ->groupBy('viewed_on')->selectRaw('viewed_on, COUNT(*) AS views')
            ->pluck('views', 'viewed_on');

        return self::fill($counts->all(), $days);
    }

    public static function referrers(Cv $cv, int $limit = 5): array
    {
        return DB::table('cv_views')->where('cv_id', $cv->id)->whereNotNull('referrer_host')
            ->groupBy('referrer_host')->selectRaw('referrer_host, COUNT(*) AS views')
            ->orderByDesc('views')->limit($limit)->get()->all();
    }

    /** turns date => count into an unbroken run of the last n days, oldest first */
    public static function fill(array $counts, int $days): array
    {
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $series[$day] = (int) ($counts[$day] ?? 0);
        }

        return $series;
    }

    /** a salted daily hash de-duplicates visits without storing anyone's address */
    private static function visitor(Request $request, ?User $viewer, int $ownerId): ?string
    {
        $agent = (string) $request->userAgent();
        if ($viewer?->id === $ownerId || $agent === '' || preg_match('/bot|crawl|spider|preview|monitor|uptime|curl|wget/i', $agent)) {
            return null;
        }

        return hash('sha256', now()->toDateString().'|'.$request->ip().'|'.$agent.'|'.config('app.key'));
    }

    private static function referrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST) ?: null;

        return $host && $host !== $request->getHost() ? substr($host, 0, 100) : null;
    }
}
