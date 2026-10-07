<x-layouts.app :title="__('Jobs')">
    <div class="container-page py-8">
        <h1 class="text-3xl">{{ __('Jobs for students and graduates') }}</h1>
        <p class="mt-1 max-w-3xl text-muted">{{ __('Internships, placement years, graduate roles and part-time jobs in the UK, gathered daily from job boards. Applications happen on the board each job comes from.') }}</p>

        <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Kind of role') }}">
            <a class="btn btn-sm {{ $kind === '' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('jobs', array_filter(['q' => $q, 'where' => $where])) }}" @if($kind === '') aria-current="page" @endif>{{ __('All roles') }}</a>
            @foreach (\App\Models\JobListing::kindLabels() as $value => $label)
                <a class="btn btn-sm {{ $kind === $value ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('jobs', array_filter(['kind' => $value, 'q' => $q, 'where' => $where])) }}" @if($kind === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('jobs') }}" role="search" class="card mt-4 grid gap-4 p-5 md:grid-cols-12 md:items-end">
            @if ($kind !== '')<input type="hidden" name="kind" value="{{ $kind }}">@endif
            <div class="md:col-span-5">
                <label for="job-q" class="field-label">{{ __('Search') }}</label>
                <input id="job-q" name="q" type="search" value="{{ $q }}" class="input" placeholder="{{ __('For example: software, finance or Python') }}" autocomplete="off">
            </div>
            <div class="md:col-span-5">
                <label for="job-where" class="field-label">{{ __('Location') }}</label>
                <input id="job-where" name="where" type="text" value="{{ $where }}" class="input" placeholder="{{ __('For example: London or Birmingham') }}" autocomplete="off">
            </div>
            <div class="md:col-span-2"><button type="submit" class="btn btn-primary w-full">{{ __('Search') }}</button></div>
        </form>

        @if ($jobs->isEmpty())
            <p class="card mt-6 p-6 text-muted">{{ $q !== '' || $where !== '' || $kind !== '' ? __('No jobs match. Try fewer words or another location.') : __('No jobs have been gathered yet. They are added once a day.') }}</p>
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
                                <span class="badge shrink-0">{{ \App\Models\JobListing::kindLabels()[$job->kind] }}</span>
                            </div>
                            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                @if ($job->salaryText())<div><dt class="inline text-muted">{{ __('Salary') }}:</dt> <dd class="inline">{{ $job->salaryText() }}</dd></div>@endif
                                @if ($job->posted_at)<div><dt class="inline text-muted">{{ __('Posted') }}:</dt> <dd class="inline">{{ $job->posted_at->translatedFormat('j M Y') }}</dd></div>@endif
                                @if ($job->closes_at)<div><dt class="inline text-muted">{{ __('Closes') }}:</dt> <dd class="inline">{{ $job->closes_at->translatedFormat('j M Y') }}</dd></div>@endif
                            </dl>
                            @if ($job->description)<p class="mt-3 text-sm">{{ \Illuminate\Support\Str::limit($job->description, 280) }}</p>@endif
                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <a class="btn btn-primary btn-sm" href="{{ $job->url }}" target="_blank" rel="noopener nofollow">{{ __('View and apply') }}<x-new-tab /></a>
                                @auth
                                    <a class="btn btn-secondary btn-sm" href="{{ route('check', ['job' => $job->id]) }}">{{ __('Check my CV against this job') }}</a>
                                @endauth
                                @if ($job->source === 'adzuna')
                                    {{-- adzuna's terms require this label on every listing, linked to adzuna --}}
                                    <span class="ms-auto inline-flex items-center gap-1 text-sm text-muted"><a href="https://www.adzuna.co.uk" rel="noopener nofollow">{{ __('Jobs') }}</a> {{ __('by') }}
                                        <a href="https://www.adzuna.co.uk" rel="noopener nofollow">@if (file_exists(public_path('images/adzuna-logo.svg')))<img src="{{ asset('images/adzuna-logo.svg') }}" alt="Adzuna" width="80" height="23" class="inline h-[23px] w-auto">@else Adzuna @endif</a></span>
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
