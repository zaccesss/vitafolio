@php
    $change = function (int $now, int $before) use ($days) {
        if ($before === 0) {
            return $now === 0 ? __('No change on the previous :days days', ['days' => $days]) : __('New this period');
        }
        $pct = (int) round(100 * ($now - $before) / $before);

        return match (true) {
            $pct === 0 => __('No change on the previous :days days', ['days' => $days]),
            $pct > 0 => __('Up :percent% on the previous :days days', ['percent' => $pct, 'days' => $days]),
            default => __('Down :percent% on the previous :days days', ['percent' => abs($pct), 'days' => $days]),
        };
    };
    $viewsPerCv = collect($byCv)->mapWithKeys(fn ($row) => [$row['cv']->title => $row['counts']['view'] + $row['counts']['qr']])->filter()->all();
@endphp
<x-layouts.app :title="__('Analytics')" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl">{{ __('Analytics') }}</h1>
                <p class="mt-1 text-muted">{{ __('How your CVs and profile are doing. Each visitor counts once a day and your own visits never count.') }}</p>
            </div>
            <nav aria-label="{{ __('Period') }}" class="flex gap-1 rounded-xl border border-line bg-surface p-1">
                @foreach (\App\Support\Analytics::RANGES as $range)
                    <a href="{{ route('analytics', ['days' => $range]) }}" class="segment-link" @if($range === $days) aria-current="page" @endif>{{ __(':count days', ['count' => $range]) }}</a>
                @endforeach
            </nav>
        </div>

        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cards as [$label, $now, $before])
                <li class="card p-5">
                    <p class="text-sm text-muted">{{ __($label) }}</p>
                    <p class="mt-1 text-3xl font-semibold">{{ number_format($now) }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $change($now, $before) }}</p>
                </li>
            @endforeach
        </ul>

        <x-chart.line class="mt-6" id="views-chart" :title="__('Views over the last :days days', ['days' => $days])" :datasets="collect($series)->mapWithKeys(fn ($values, $label) => [__($label) => $values])->all()" />
        @if ($busiest)
            <p class="mt-2 text-sm text-muted">{{ trans_choice('{1} Busiest day: :date, with :count view.|[2,*] Busiest day: :date, with :count views.', $busiestCount, ['date' => $busiest->translatedFormat('l j F')]) }}</p>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-chart.bars id="per-cv" :title="__('Views per CV')" :rows="$viewsPerCv" :empty="__('No views in this period yet. Share a link to get started.')" />
            <x-chart.bars id="referrers" :title="__('Where visitors came from')" :rows="$referrers" :empty="__('No referring sites yet. Visitors who typed the link or scanned a code are not shown here.')" />
        </div>

        <section aria-labelledby="table-title" class="card mt-6 overflow-x-auto p-0">
            <h2 id="table-title" class="px-5 pt-5 text-lg">{{ __('Every CV in this period') }}</h2>
            <table class="mt-3 w-full text-start text-sm">
                <caption class="sr-only">{{ __('Views, scans, downloads and file opens for each CV over the last :days days', ['days' => $days]) }}</caption>
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th scope="col" class="px-5 py-2">{{ __('CV') }}</th>
                        @foreach (\App\Support\ViewRecorder::KINDS as $label)<th scope="col" class="px-5 py-2 text-end">{{ __($label) }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($byCv as $row)
                        <tr class="border-b border-line last:border-0">
                            <th scope="row" class="px-5 py-2 font-normal"><a href="{{ route('cv.show', $row['cv']) }}">{{ $row['cv']->title }}</a> <x-visibility class="ms-1 align-middle" :value="$row['cv']->visibility" /></th>
                            @foreach (array_keys(\App\Support\ViewRecorder::KINDS) as $kind)<td class="px-5 py-2 text-end">{{ $row['counts'][$kind] }}</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-4 text-muted">{{ __('You have no CVs yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <p class="mt-6 text-sm text-muted">{{ __('Visits are counted with a code that changes daily, so nobody can be followed from day to day.') }} <a href="{{ route('help.topic', 'analytics') }}">{{ __('How analytics work') }}</a></p>
    </div>
</x-layouts.app>
