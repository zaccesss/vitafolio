<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** an owner's numbers for one period, with the period before it for comparison */
class Analytics
{
    public const RANGES = [7, 30, 90];

    public static function forUser(User $user, int $days): array
    {
        $cvs = $user->cvs()->get(['id', 'title', 'slug', 'visibility']);
        $ids = $cvs->pluck('id')->all();
        $start = now()->subDays($days)->toDateString();
        $previousStart = now()->subDays($days * 2)->toDateString();

        $cvRows = fn (string $from, ?string $to = null) => DB::table('cv_views')->whereIn('cv_id', $ids ?: [0])
            ->where('viewed_on', '>', $from)->when($to, fn ($q) => $q->where('viewed_on', '<=', $to));

        $totals = $cvRows($start)->groupBy('kind')->selectRaw('kind, COUNT(*) AS n')->pluck('n', 'kind')->map(fn ($n) => (int) $n)->all();
        $previous = $cvRows($previousStart, $start)->groupBy('kind')->selectRaw('kind, COUNT(*) AS n')->pluck('n', 'kind')->map(fn ($n) => (int) $n)->all();
        $profileRows = fn (string $from, ?string $to = null) => DB::table('profile_views')->where('user_id', $user->id)
            ->where('viewed_on', '>', $from)->when($to, fn ($q) => $q->where('viewed_on', '<=', $to));

        $cvSeries = ViewRecorder::fill($cvRows($start)->whereIn('kind', ['view', 'qr'])->groupBy('viewed_on')
            ->selectRaw('viewed_on, COUNT(*) AS n')->pluck('n', 'viewed_on')->all(), $days);
        $profileSeries = ViewRecorder::fill($profileRows($start)->groupBy('viewed_on')
            ->selectRaw('viewed_on, COUNT(*) AS n')->pluck('n', 'viewed_on')->all(), $days);

        $perCv = $cvRows($start)->groupBy('cv_id', 'kind')->selectRaw('cv_id, kind, COUNT(*) AS n')->get()
            ->groupBy('cv_id')->map(fn ($rows) => $rows->pluck('n', 'kind')->map(fn ($n) => (int) $n)->all());
        $byCv = $cvs->map(fn ($cv) => [
            'cv' => $cv,
            'counts' => array_replace(array_fill_keys(array_keys(ViewRecorder::KINDS), 0), $perCv[$cv->id] ?? []),
        ])->sortByDesc(fn ($row) => $row['counts']['view'] + $row['counts']['qr'])->values()->all();

        $referrers = DB::query()->fromSub(
            $cvRows($start)->whereNotNull('referrer_host')->select('referrer_host')
                ->unionAll($profileRows($start)->whereNotNull('referrer_host')->select('referrer_host')),
            'r'
        )->groupBy('referrer_host')->selectRaw('referrer_host, COUNT(*) AS n')->orderByDesc('n')->limit(8)
            ->pluck('n', 'referrer_host')->map(fn ($n) => (int) $n)->all();

        $combined = [];
        foreach ($cvSeries as $day => $n) {
            $combined[$day] = $n + $profileSeries[$day];
        }
        $busiest = max($combined ?: [0]) > 0 ? array_search(max($combined), $combined, true) : null;

        $views = ($totals['view'] ?? 0) + ($totals['qr'] ?? 0);
        $previousViews = ($previous['view'] ?? 0) + ($previous['qr'] ?? 0);

        return [
            'days' => $days,
            'cards' => [
                ['CV views', $views, $previousViews],
                ['Profile views', $profileRows($start)->count(), $profileRows($previousStart, $start)->count()],
                ['PDF downloads', $totals['pdf'] ?? 0, $previous['pdf'] ?? 0],
                ['QR code scans', $totals['qr'] ?? 0, $previous['qr'] ?? 0],
            ],
            'series' => ['CV views' => $cvSeries, 'Profile views' => $profileSeries],
            'byCv' => $byCv,
            'referrers' => $referrers,
            'busiest' => $busiest ? Carbon::parse($busiest) : null,
            'busiestCount' => $busiest ? $combined[$busiest] : 0,
        ];
    }
}
