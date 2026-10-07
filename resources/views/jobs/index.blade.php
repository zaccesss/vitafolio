<x-layouts.app :title="__('Jobs')">
    <div class="container-page py-8">
        <h1 class="text-3xl">{{ __('Jobs for students and graduates') }}</h1>
        <p class="mt-1 max-w-3xl text-muted">{{ __('Internships, placement years, spring weeks, graduate roles and part-time jobs for the :from to :to recruitment cycle, gathered daily from employers\' own careers sites and job boards. Each one links to the page where you apply.', ['from' => config('vitafolio.jobs_cycle')[0], 'to' => config('vitafolio.jobs_cycle')[1]]) }}</p>

        <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Kind of role') }}">
            <a class="btn btn-sm {{ $kind === '' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('jobs', array_filter(['sector' => $sector, 'q' => $q, 'where' => $where])) }}" @if($kind === '') aria-current="page" @endif>{{ __('All roles') }}</a>
            @foreach (\App\Models\JobListing::kindLabels() as $value => $label)
                <a class="btn btn-sm {{ $kind === $value ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('jobs', array_filter(['kind' => $value, 'sector' => $sector, 'q' => $q, 'where' => $where])) }}" @if($kind === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('jobs') }}" role="search" class="card mt-4 grid gap-4 p-5 md:grid-cols-12 md:items-end">
            @if ($kind !== '')<input type="hidden" name="kind" value="{{ $kind }}">@endif
            <div class="md:col-span-4">
                <label for="job-q" class="field-label">{{ __('Job title or employer') }}</label>
                <input id="job-q" name="q" type="search" value="{{ $q }}" class="input" placeholder="{{ __('For example: software engineer or Arup') }}" autocomplete="off">
            </div>
            <div class="md:col-span-3">
                <label for="job-sector" class="field-label">{{ __('Field') }}</label>
                <select id="job-sector" name="sector" class="input">
                    <option value="">{{ __('All fields') }}</option>
                    @foreach (\App\Support\Jobs\Sector::labels() as $value => $label)
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

        @if ($jobs->isEmpty())
            <p class="card mt-6 p-6 text-muted">{{ $q !== '' || $where !== '' || $kind !== '' || $sector !== '' ? __('No jobs match. Try fewer words or another location.') : __('No jobs have been gathered yet. They are added once a day.') }}</p>
        @else
            <ul class="mt-6 grid gap-4">
                @foreach ($jobs as $job)
                    <li>
                        <article class="card p-5" aria-labelledby="job-{{ $job->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 id="job-{{ $job->id }}" class="text-lg">{{ $job->title }}</h2>
                                    <p class="text-sm text-muted">{{ collect([$job->company, $job->location])->filter()->implode(' · ') }}</p>
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <span class="badge">{{ \App\Models\JobListing::kindLabels()[$job->kind] }}</span>
                                    <span class="badge">{{ \App\Support\Jobs\Sector::labels()[$job->sector] ?? __('Other') }}</span>
                                </div>
                            </div>
                            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                @if ($job->salaryText())<div><dt class="inline text-muted">{{ __('Salary') }}:</dt> <dd class="inline">{{ $job->salaryText() }}</dd></div>@endif
                                @if ($job->posted_at)<div><dt class="inline text-muted">{{ __('Posted') }}:</dt> <dd class="inline">{{ $job->posted_at->translatedFormat('j M Y') }}</dd></div>@endif
                                @if ($job->closes_at)<div><dt class="inline text-muted">{{ __('Closes') }}:</dt> <dd class="inline">{{ $job->closes_at->translatedFormat('j M Y') }}</dd></div>@endif
                            </dl>
                            @if ($job->description && $job->source !== 'employer')<p class="mt-3 text-sm">{{ \Illuminate\Support\Str::limit($job->description, 280) }}</p>@endif
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <a class="btn btn-primary btn-sm" href="{{ $job->url }}" target="_blank" rel="noopener nofollow">{{ __('View and apply') }}<x-new-tab /></a>
                                @auth
                                    @if ($job->description)
                                        <a class="btn btn-secondary btn-sm" href="{{ route('check', ['job' => $job->id]) }}">{{ __('Check my CV against this job') }}</a>
                                    @endif
                                @endauth
                                @if ($job->source === 'adzuna')
                                    {{-- adzuna's terms require this label on every listing, at least 116 by 23 pixels and linked to adzuna --}}
                                    <span class="ms-auto inline-flex items-center gap-1 text-sm text-muted"><a href="https://www.adzuna.co.uk" rel="noopener nofollow">{{ __('Jobs') }}</a> {{ __('by') }}
                                        <a href="https://www.adzuna.co.uk" rel="noopener nofollow" class="inline-flex min-h-[23px] min-w-[116px] items-center text-base font-semibold">@if (file_exists(public_path('images/adzuna-logo.svg')))<img src="{{ asset('images/adzuna-logo.svg') }}" alt="Adzuna" width="116" height="23" class="h-auto min-h-[23px] w-[116px]">@else Adzuna @endif</a></span>
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
