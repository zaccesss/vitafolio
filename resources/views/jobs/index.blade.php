<x-layouts.app :title="__('Jobs')">
    @php
        $filters = array_filter(['sector' => $sector, 'q' => $q, 'where' => $where]);
        $kinds = \App\Models\JobListing::kindLabels();
        $fields = \App\Support\Jobs\Sector::labels();
    @endphp
    <div class="container-page py-8">
        <header class="max-w-3xl">
            <h1 class="text-3xl">{{ __('Jobs for students and graduates') }}</h1>
            <p class="mt-2 text-muted">{{ __('Internships, placement years, spring weeks, graduate roles, apprenticeships and part-time jobs for the :from to :to recruitment cycle, gathered daily from employers\' own careers sites and job boards. Each one links to the page where you apply.', ['from' => config('vitafolio.jobs_cycle')[0], 'to' => config('vitafolio.jobs_cycle')[1]]) }}</p>
        </header>

        <form method="GET" action="{{ route('jobs') }}" role="search" class="card mt-6 grid gap-4 p-5 md:grid-cols-12 md:items-end">
            @if ($kind !== '')<input type="hidden" name="kind" value="{{ $kind }}">@endif
            <div class="md:col-span-4">
                <label for="job-q" class="field-label">{{ __('Job title or employer') }}</label>
                <input id="job-q" name="q" type="search" value="{{ $q }}" class="input" placeholder="{{ __('For example: software engineer or Arup') }}" autocomplete="off">
            </div>
            <div class="md:col-span-3">
                <label for="job-sector" class="field-label">{{ __('Field') }}</label>
                <select id="job-sector" name="sector" class="input">
                    <option value="">{{ __('All fields') }}</option>
                    @foreach ($fields as $value => $label)
                        <option value="{{ $value }}" @selected($sector === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label for="job-where" class="field-label">{{ __('Location') }}</label>
                <input id="job-where" name="where" type="text" value="{{ $where }}" class="input" placeholder="{{ __('For example: London or Birmingham') }}" autocomplete="off">
            </div>
            <div class="md:col-span-2"><button type="submit" class="btn btn-primary w-full">{{ __('Search') }}</button></div>
        </form>

        {{-- the selected tab is bold with a thick underline, so it never relies on colour alone --}}
        <nav class="mt-6 flex gap-1 overflow-x-auto border-b-2 border-line" aria-label="{{ __('Kind of role') }}">
            <a class="tab-link" href="{{ route('jobs', $filters) }}" @if ($kind === '') aria-current="page" @endif>{{ __('All roles') }} <span class="ms-1 text-sm font-normal text-muted">{{ number_format($counts->sum()) }}</span></a>
            @foreach ($kinds as $value => $label)
                <a class="tab-link" href="{{ route('jobs', ['kind' => $value] + $filters) }}" @if ($kind === $value) aria-current="page" @endif>{{ $label }} <span class="ms-1 text-sm font-normal text-muted">{{ number_format($counts[$value] ?? 0) }}</span></a>
            @endforeach
        </nav>

        @if ($jobs->isEmpty())
            <p class="card mt-6 p-6 text-muted">{{ $q !== '' || $where !== '' || $kind !== '' || $sector !== '' ? __('No jobs match. Try fewer words or another location.') : __('No jobs have been gathered yet. They are added once a day.') }}</p>
        @else
            <p class="mt-4 text-sm text-muted">{{ __('Jobs found: :count', ['count' => number_format($jobs->total())]) }}</p>
            <ul class="mt-3 grid gap-3">
                @foreach ($jobs as $job)
                    <li>
                        <article class="card p-4 sm:p-5" aria-labelledby="job-{{ $job->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                                <div class="min-w-0">
                                    <h2 id="job-{{ $job->id }}" class="text-lg leading-snug">{{ $job->title }}</h2>
                                    <p class="mt-0.5 text-sm">
                                        <span class="font-semibold">{{ $job->company ?? __('An employer') }}</span>
                                        @if ($job->locationText())<span class="text-muted"> · {{ $job->locationText() }}</span>@endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    @if ($kind === '')<span class="badge">{{ $kinds[$job->kind] ?? '' }}</span>@endif
                                    <span class="badge">{{ $fields[$job->sector] ?? __('Other') }}</span>
                                </div>
                            </div>
                            <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
                                @if ($job->salaryText())<span>{{ $job->salaryText() }}</span>@endif
                                @if ($job->posted_at)<span>{{ __('Posted :date', ['date' => $job->posted_at->translatedFormat('j M Y')]) }}</span>@endif
                                @if ($job->closes_at)<span class="font-semibold text-ink">{{ __('Closes :date', ['date' => $job->closes_at->translatedFormat('j M Y')]) }}</span>@endif
                            </p>
                            @if ($job->description && $job->source !== 'employer')<p class="mt-2 line-clamp-2 text-sm">{{ $job->description }}</p>@endif
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <a class="btn btn-primary btn-sm" href="{{ route('jobs.go', $job) }}" target="_blank" rel="noopener nofollow">{{ __('View and apply') }}<x-new-tab /></a>
                                @auth
                                    @if (in_array($job->id, $saved, true))
                                        <a class="btn btn-secondary btn-sm" href="{{ route('applications.index') }}">{{ __('Saved') }}<span aria-hidden="true"> ✓</span></a>
                                    @else
                                        <form method="POST" action="{{ route('jobs.save', $job) }}">@csrf<button type="submit" class="btn btn-secondary btn-sm">{{ __('Save') }}</button></form>
                                    @endif
                                    @if ($job->description)
                                        <a class="btn btn-secondary btn-sm" href="{{ route('check', ['job' => $job->id]) }}">{{ __('Check my CV against this job') }}</a>
                                    @endif
                                @endauth
                                @if ($job->source === 'adzuna')
                                    {{-- adzuna's terms require this label on every listing, at least 116 by 23 pixels and linked to adzuna --}}
                                    <span class="ms-auto inline-flex items-center gap-1 text-xs text-muted"><a href="https://www.adzuna.co.uk" rel="noopener nofollow" class="text-muted">{{ __('Jobs') }}</a> {{ __('by') }}
                                        <a href="https://www.adzuna.co.uk" rel="noopener nofollow" class="inline-flex min-h-[23px] min-w-[116px] items-center text-muted">@if (file_exists(public_path('images/adzuna-logo.svg')))<img src="{{ asset('images/adzuna-logo.svg') }}" alt="Adzuna" width="116" height="23" class="h-auto min-h-[23px] w-[116px]">@else Adzuna @endif</a></span>
                                @elseif ($job->source === 'employer')
                                    <span class="ms-auto text-sm text-muted">{{ __('From the employer\'s own careers site') }}</span>
                                @else
                                    <span class="ms-auto text-sm text-muted">{{ __('From :source', ['source' => \App\Models\JobListing::SOURCES[$job->source]]) }}</span>
                                @endif
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $jobs->links() }}</div>
        @endif
    </div>
</x-layouts.app>
