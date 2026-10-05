@php
    $max = max(1, max($series ?: [0]));
    $days = array_keys($series);
    $barWidth = 100 / max(1, count($series));
@endphp
<x-layouts.app title="My CVs" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl">My CVs</h1>
                <p class="mt-1 text-muted">Hello {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}. You have {{ $cvs->count() }} {{ \Illuminate\Support\Str::plural('CV', $cvs->count()) }}.</p>
            </div>
            @if ($canCreate)
                <form method="POST" action="{{ route('cvs.store') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label for="new-title" class="field-label text-sm">New CV name</label>
                        <input id="new-title" name="title" class="input min-h-11 py-2" required maxlength="80" placeholder="For example: Data analyst CV" @error('title') aria-invalid="true" aria-describedby="new-title-error" @enderror>
                    </div>
                    <button type="submit" class="btn btn-primary">Create CV</button>
                    @error('title')<p id="new-title-error" class="field-error w-full">{{ $message }}</p>@enderror
                </form>
            @else
                <p class="badge">You have reached the limit of {{ config('vitafolio.max_cvs_per_user') }} CVs</p>
            @endif
        </div>

        <ul class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach ($cvs as $cv)
                <li class="card flex flex-col gap-4 p-5 accent-{{ $cv->accent }} {{ $selected?->is($cv) ? 'ring-2 ring-brand' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-lg">{{ $cv->title }}</h2>
                            <p class="truncate text-sm text-muted">{{ preg_replace('#^https?://#', '', route('cv.show', $cv)) }}</p>
                        </div>
                        <span class="badge shrink-0">
                            @if ($cv->hidden_at) Hidden by a moderator @else {{ ucfirst($cv->visibility) }} @endif
                        </span>
                    </div>
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <div><dt class="text-muted">Views</dt><dd class="text-lg font-semibold">{{ number_format($cv->view_count) }}</dd></div>
                        <div><dt class="text-muted">Skills</dt><dd class="text-lg font-semibold">{{ $cv->tags->count() }}</dd></div>
                        <div><dt class="text-muted">File</dt><dd class="text-lg font-semibold">{{ $cv->document ? 'Yes' : 'No' }}</dd></div>
                    </dl>
                    <p class="text-sm text-muted">Updated {{ $cv->updated_at->diffForHumans() }}</p>
                    <div class="mt-auto flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-primary" href="{{ route('cvs.edit', $cv) }}">Edit<span class="sr-only"> {{ $cv->title }}</span></a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('cv.show', $cv) }}">View<span class="sr-only"> {{ $cv->title }}</span></a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('dashboard', ['cv' => $cv->slug]) }}#stats" @if($selected?->is($cv)) aria-current="true" @endif>Statistics<span class="sr-only"> for {{ $cv->title }}</span></a>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($selected)
            <section id="stats" class="card mt-10 p-6" aria-labelledby="stats-title">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="stats-title" class="text-xl">Statistics: {{ $selected->title }}</h2>
                        <p class="text-sm text-muted">Each visitor is counted once a day. Your own visits are never counted.</p>
                    </div>
                    <dl class="flex gap-8">
                        <div><dt class="text-sm text-muted">Last 30 days</dt><dd class="text-2xl font-bold">{{ number_format($recentViews) }}</dd></div>
                        <div><dt class="text-sm text-muted">All time</dt><dd class="text-2xl font-bold">{{ number_format($selected->view_count) }}</dd></div>
                    </dl>
                </div>

                {{-- the chart is a picture of the table below, which carries the same numbers for screen readers --}}
                <svg class="mt-6 h-48 w-full" viewBox="0 0 100 40" preserveAspectRatio="none" role="img"
                     aria-label="Daily views over the last 30 days, {{ $recentViews }} in total. The table below has the exact numbers.">
                    <line x1="0" y1="39.8" x2="100" y2="39.8" class="stroke-line" stroke-width="0.4"/>
                    @foreach ($series as $day => $views)
                        @php($height = $views === 0 ? 0.6 : max(1.5, 38 * $views / $max))
                        <rect x="{{ $loop->index * $barWidth + $barWidth * 0.15 }}" y="{{ 39.6 - $height }}" width="{{ $barWidth * 0.7 }}" height="{{ $height }}"
                              rx="0.4" class="{{ $views === 0 ? 'fill-line' : 'fill-brand' }}"><title>{{ \Illuminate\Support\Carbon::parse($day)->format('j M') }}: {{ $views }}</title></rect>
                    @endforeach
                </svg>
                <div class="mt-1 flex justify-between text-xs text-muted" aria-hidden="true">
                    <span>{{ \Illuminate\Support\Carbon::parse($days[0])->format('j M') }}</span>
                    <span>{{ \Illuminate\Support\Carbon::parse(end($days))->format('j M') }}</span>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <details>
                        <summary class="cursor-pointer font-semibold">Daily views as a table</summary>
                        <table class="mt-3 w-full text-sm">
                            <thead><tr class="border-b border-line text-left"><th class="py-1.5" scope="col">Date</th><th scope="col" class="text-right">Views</th></tr></thead>
                            <tbody>
                                @foreach (array_reverse($series, true) as $day => $views)
                                    <tr class="border-b border-line"><td class="py-1.5">{{ \Illuminate\Support\Carbon::parse($day)->format('l j F') }}</td><td class="text-right">{{ $views }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                    <div>
                        <h3 class="font-semibold">Where visitors came from</h3>
                        @if ($referrers === [])
                            <p class="mt-2 text-sm text-muted">No referring websites yet. Visits from links in emails, apps and QR codes do not show a source.</p>
                        @else
                            <ul class="mt-2 space-y-1.5 text-sm">
                                @foreach ($referrers as $ref)
                                    <li class="flex justify-between gap-4 border-b border-line py-1"><span>{{ $ref->referrer_host }}</span><span class="font-semibold">{{ $ref->views }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
