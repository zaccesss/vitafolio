@props(['datasets', 'title', 'id'])
@php
    // a hand-drawn svg: no chart library and no script; every line differs by dash as well as colour
    $w = 640; $h = 240; $left = 40; $bottom = 28; $top = 12; $right = 12;
    $plotW = $w - $left - $right; $plotH = $h - $top - $bottom;
    $first = reset($datasets);
    $days = array_keys($first);
    $count = max(1, count($days) - 1);
    $max = max(1, ...array_map(fn ($s) => max($s ?: [0]), array_values($datasets)));
    $step = max(1, (int) ceil($max / 4));
    $top_value = $step * 4;
    $x = fn ($i) => round($left + $plotW * $i / $count, 1);
    $y = fn ($v) => round($top + $plotH - $plotH * $v / $top_value, 1);
    $styles = [['stroke-brand', '', 'fill-brand'], ['stroke-brass', '6 5', 'fill-brass']];
    $labelEvery = max(1, (int) ceil(count($days) / 7));
    $totals = array_map('array_sum', $datasets);
    $summary = $title.': '.collect($totals)->map(fn ($t, $label) => $label.' '.$t)->implode(', ').' over '.count($days).' days.';
@endphp
<figure {{ $attributes->merge(['class' => 'card p-5']) }} aria-labelledby="{{ $id }}-title">
    <figcaption id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</figcaption>
    <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted">
        @foreach (array_keys($datasets) as $i => $label)
            <li class="inline-flex items-center gap-2">
                <svg width="28" height="10" aria-hidden="true"><line x1="0" y1="5" x2="28" y2="5" class="{{ $styles[$i % 2][0] }}" stroke-width="3" @if($styles[$i % 2][1]) stroke-dasharray="{{ $styles[$i % 2][1] }}" @endif /></svg>
                {{ $label }} <span class="font-semibold text-ink">{{ $totals[$label] }}</span>
            </li>
        @endforeach
    </ul>
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="mt-3 h-auto w-full" role="img" aria-label="{{ $summary }} The table below has every value.">
        @for ($t = 0; $t <= 4; $t++)
            <line x1="{{ $left }}" x2="{{ $w - $right }}" y1="{{ $y($t * $step) }}" y2="{{ $y($t * $step) }}" class="stroke-line" stroke-width="1" />
            <text x="{{ $left - 8 }}" y="{{ $y($t * $step) + 4 }}" text-anchor="end" class="fill-muted text-[11px]">{{ $t * $step }}</text>
        @endfor
        @foreach ($days as $i => $day)
            @if ($i % $labelEvery === 0 || $i === count($days) - 1)
                <text x="{{ $x($i) }}" y="{{ $h - 8 }}" text-anchor="middle" class="fill-muted text-[11px]">{{ \Illuminate\Support\Carbon::parse($day)->format('j M') }}</text>
            @endif
        @endforeach
        @foreach (array_values($datasets) as $i => $series)
            @php
                $points = collect(array_values($series))->map(fn ($v, $j) => $x($j).','.$y($v))->implode(' ');
                [$stroke, $dash, $fill] = $styles[$i % 2];
            @endphp
            @if ($i === 0)
                <polygon points="{{ $x(0) }},{{ $y(0) }} {{ $points }} {{ $x(count($series) - 1) }},{{ $y(0) }}" class="{{ $fill }}" opacity="0.12" />
            @endif
            <polyline points="{{ $points }}" fill="none" class="{{ $stroke }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" @if($dash) stroke-dasharray="{{ $dash }}" @endif />
        @endforeach
    </svg>
    <details class="mt-3">
        <summary class="cursor-pointer text-sm font-semibold">Show as a table</summary>
        <div class="mt-2 max-h-72 overflow-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">{{ $title }} by day</caption>
                <thead class="border-b border-line text-muted"><tr><th scope="col" class="py-1.5">Day</th>@foreach (array_keys($datasets) as $label)<th scope="col" class="py-1.5 text-right">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach (array_reverse($days) as $day)
                        <tr class="border-b border-line"><th scope="row" class="py-1.5 font-normal">{{ \Illuminate\Support\Carbon::parse($day)->format('D j F') }}</th>@foreach ($datasets as $series)<td class="py-1.5 text-right">{{ $series[$day] }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
