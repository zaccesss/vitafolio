<x-layouts.app :title="__('Check a CV')" noindex>
    <div class="container-page py-8">
        <h1 class="text-3xl">{{ __('Check a CV') }}</h1>
        <p class="mt-1 max-w-3xl text-muted">{{ __('See your CV the way an applicant tracking system reads it: what it can find, what it misses and how to fix it. Uploaded files are read once and never stored.') }}</p>

        <form method="POST" action="{{ route('check.run') }}" enctype="multipart/form-data" class="card mt-6 grid gap-5 p-6">
            @csrf
            <fieldset class="grid gap-3" x-data="cvSource">
                <legend class="field-label">{{ __('Which CV?') }}</legend>
                <label class="flex items-center gap-3"><input type="radio" name="source" value="file" class="check" @checked(old('source', $cvs->isEmpty() ? 'file' : 'cv') === 'file')> {{ __('Upload a PDF or Word (.docx) file') }}</label>
                <x-field name="resume" :label="__('CV file')" type="file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" :hint="__('Up to 5 MB.')" />
                @if ($cvs->isNotEmpty())
                    <label class="flex items-center gap-3"><input type="radio" name="source" value="cv" class="check" @checked(old('source', 'cv') === 'cv')> {{ __('One of my Vitafolio CVs') }}</label>
                    <div>
                        <label class="field-label" for="f-cv">{{ __('Vitafolio CV') }}</label>
                        <select id="f-cv" name="cv" class="input sm:max-w-md">
                            @foreach ($cvs as $cv)
                                <option value="{{ $cv->id }}" @selected((int) old('cv') === $cv->id)>{{ $cv->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </fieldset>
            @if ($job)
                <div class="alert alert-info">
                    <p>{{ __('Checking against :title at :company. The advert is filled in below.', ['title' => $job->title, 'company' => $job->company ?? __('an employer')]) }}</p>
                    @if ($job->source !== 'employer')
                        {{-- job boards share only a preview of each advert through their APIs --}}
                        <p class="mt-2">{{ __('Job boards share only the start of each advert. For a full check, open the advert, copy all of it and paste it over the text below.') }}</p>
                        <a class="btn btn-secondary btn-sm mt-3" href="{{ $job->url }}" target="_blank" rel="noopener nofollow">{{ __('Open the full advert') }}<x-new-tab /></a>
                    @endif
                </div>
            @endif
            <x-field name="job_advert" :label="__('Job advert')" type="textarea" rows="6" maxlength="10000" :value="$advert"
                     :hint="__('Paste the advert to see which of its keywords your CV has and which it is missing.')" />
            <div><button type="submit" class="btn btn-primary">{{ __('Check my CV') }}</button></div>
        </form>

        @if ($report)
            <section class="mt-8 grid gap-6" aria-labelledby="result-title">
                <div class="card p-6">
                    <h2 id="result-title" class="text-2xl">{{ __('Score: :score out of 100', ['score' => $report['score']['total']]) }}</h2>
                    <p class="mt-1 text-lg font-semibold">{{ $report['score']['grade'] }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('Checked: :name', ['name' => $checked]) }}</p>
                    <x-english-only class="mt-3" />
                    <p class="mt-3 text-sm text-muted">{{ __('This score is guidance based on how common applicant tracking systems read CVs. Each employer sets up its own system differently. Most rank and search applications rather than reject them automatically, so a recruiter will usually still read your CV.') }}</p>
                    <table class="mt-4 w-full max-w-xl text-sm">
                        <caption class="sr-only">{{ __('Score by area') }}</caption>
                        <thead><tr class="border-b border-line text-start"><th scope="col" class="py-2 text-start">{{ __('Area') }}</th><th scope="col" class="py-2 text-end">{{ __('Score') }}</th></tr></thead>
                        <tbody>
                            @foreach ($report['score']['areas'] as $area)
                                <tr class="border-b border-line last:border-0"><th scope="row" class="py-2 text-start font-normal">{{ $area['label'] }}</th><td class="py-2 text-end">{{ $area['score'] }} / {{ $area['max'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-4 flex flex-wrap gap-3">
                        <a class="btn btn-secondary btn-sm" href="{{ route('check.download', 'md') }}">{{ __('Download the report (Markdown)') }}</a>
                        <a class="btn btn-secondary btn-sm" href="{{ route('check.download', 'json') }}">{{ __('Download the parsed CV (JSON)') }}</a>
                    </p>
                </div>
                @foreach (['suggestions' => 'Suggestions', 'formatting' => 'Formatting issues', 'missing' => 'Missing', 'found' => 'What was found'] as $key => $title)
                    <div class="card p-6">
                        <h3 class="text-xl">{{ __($title) }}</h3>
                        @if ($report[$key] === [])
                            <p class="mt-2 text-muted">{{ __('Nothing to report.') }}</p>
                        @else
                            <ul class="mt-3 list-disc space-y-1 ps-6">@foreach ($report[$key] as $line)<li>{{ $line }}</li>@endforeach</ul>
                        @endif
                    </div>
                @endforeach
                @if ($report['keywords'])
                    <div class="card p-6">
                        <h3 class="text-xl">{{ __('Job advert keywords') }}</h3>
                        <p class="mt-3"><strong>{{ __('In your CV:') }}</strong> {{ $report['keywords']['matched'] === [] ? __('none') : implode(', ', $report['keywords']['matched']) }}</p>
                        <p class="mt-1"><strong>{{ __('Not in your CV:') }}</strong> {{ $report['keywords']['missing'] === [] ? __('none') : implode(', ', $report['keywords']['missing']) }}</p>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-layouts.app>
