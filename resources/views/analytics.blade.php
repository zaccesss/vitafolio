@php
    $change = function (int $now, int $before) use ($days) {
        if ($before === 0) {
            return $now === 0 ? 'No change on the previous '.$days.' days' : 'New this period';
        }
        $pct = (int) round(100 * ($now - $before) / $before);

        return ($pct === 0 ? 'No change' : ($pct > 0 ? 'Up '.$pct.'%' : 'Down '.abs($pct).'%')).' on the previous '.$days.' days';
    };
    $viewsPerCv = collect($byCv)->mapWithKeys(fn ($row) => [$row['cv']->title => $row['counts']['view'] + $row['counts']['qr']])->filter()->all();
@endphp
<x-layouts.app title="Analytics" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl">Analytics</h1>
                <p class="mt-1 text-muted">How your CVs and profile are doing. Each visitor counts once a day and your own visits never count.</p>
            </div>
            <nav aria-label="Period" class="flex gap-1 rounded-xl border border-line bg-surface p-1">
                @foreach (\App\Support\Analytics::RANGES as $range)
                    <a href="{{ route('analytics', ['days' => $range]) }}" class="segment-link" @if($range === $days) aria-current="page" @endif>{{ $range }} days</a>
                @endforeach
            </nav>
        </div>

        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cards as [$label, $now, $before])
                <li class="card p-5">
                    <p class="text-sm text-muted">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-semibold">{{ number_format($now) }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $change($now, $before) }}</p>
                </li>
            @endforeach
        </ul>

        <x-chart.line class="mt-6" id="views-chart" title="Views over the last {{ $days }} days" :datasets="$series" />
        @if ($busiest)
            <p class="mt-2 text-sm text-muted">Busiest day: {{ $busiest->format('l j F') }}, with {{ $busiestCount }} {{ $busiestCount === 1 ? 'view' : 'views' }}.</p>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-chart.bars id="per-cv" title="Views per CV" :rows="$viewsPerCv" empty="No views in this period yet. Share a link to get started." />
            <x-chart.bars id="referrers" title="Where visitors came from" :rows="$referrers" empty="No referring sites yet. Visitors who typed the link or scanned a code are not shown here." />
        </div>

        <section aria-labelledby="table-title" class="card mt-6 overflow-x-auto p-0">
            <h2 id="table-title" class="px-5 pt-5 text-lg">Every CV in this period</h2>
            <table class="mt-3 w-full text-left text-sm">
                <caption class="sr-only">Views, scans, downloads and file opens for each CV over the last {{ $days }} days</caption>
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th scope="col" class="px-5 py-2">CV</th>
                        @foreach (\App\Support\ViewRecorder::KINDS as $label)<th scope="col" class="px-5 py-2 text-right">{{ $label }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byCv as $row)
                        <tr class="border-b border-line last:border-0">
                            <th scope="row" class="px-5 py-2 font-normal"><a href="{{ route('cv.show', $row['cv']) }}">{{ $row['cv']->title }}</a> <span class="badge ml-1">{{ ucfirst($row['cv']->visibility) }}</span></th>
                            @foreach (array_keys(\App\Support\ViewRecorder::KINDS) as $kind)<td class="px-5 py-2 text-right">{{ $row['counts'][$kind] }}</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-4 text-muted">You have no CVs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <p class="mt-6 text-sm text-muted">Visits are counted with a code that changes daily, so nobody can be followed from day to day. <a href="{{ route('help.topic', 'analytics') }}">How analytics work</a>.</p>
    </div>
</x-layouts.app>
