@php
    $features = [
        [__('Every version of your CV'), __('Keep up to ten named CVs, one for each kind of role, each with an optional cover letter. Duplicate one to start the next version in seconds.')],
        [__('You choose who sees each one'), __('Make each CV public, unlisted or private. Public CVs appear in the directory; unlisted ones open only for people with the link.')],
        [__('Bring the CV you already have'), __('Upload a PDF or Word file, import JSON Resume or write it in LaTeX and compile it in your browser.')],
        [__('Show your work'), __('Add projects with links, images and short videos, each described for people who cannot see them.')],
        [__('Share it anywhere'), __('A clean link, a QR code for printed copies, a polished PDF and a preview card when the link is posted.')],
        [__('Know what is working'), __('See views, PDF downloads, QR scans and where visitors came from, counted without tracking anyone.')],
        [__('Hear from employers'), __('Visitors can message you from your CV without seeing your email address. People you worked with can endorse it, shown only once you approve.')],
        [__('Check before you apply'), __('See your CV the way an applicant tracking system reads it, with a score, what is missing and how to fix it. Paste a job advert to compare keywords. Uploads are never stored.')],
        [__('Find your next role'), __('Internships, placement years, spring weeks, graduate roles, apprenticeships and part-time jobs in every field, gathered nightly from employers and job boards.')],
        [__('Track every application'), __('Save jobs with one click or add roles found anywhere else. Move each from saved to offer with dates and notes, privately.')],
        [__('Help when you need it'), __('Open a support ticket with screenshots and follow the conversation until it is sorted, with or without an account.')],
        [__('Safe by design'), __('Passkeys, two-factor authentication, breach-checked passwords and a strict security policy on every page.')],
        [__('Built for everyone'), __('Readable in light and dark mode, usable with a keyboard or screen reader, with a plain theme for applicant tracking systems.')],
        [__('In your language'), __('Use the site in English, Spanish, French, Brazilian Portuguese, Simplified Chinese, Arabic or Urdu, with Arabic and Urdu laid out right to left. Your CV is always shown as you wrote it.')],
    ];
    $steps = [
        [__('Create your account'), __('Sign up with your email or with Google, GitHub or Microsoft. Your first CV is ready straight away.')],
        [__('Build or upload'), __('Fill in your sections, add projects and pick a theme. Or upload the file you already have.')],
        [__('Share'), __('Choose who can see it, then share the link, the QR code or the PDF.')],
    ];
@endphp
<x-layouts.app :title="__('Features')" :description="__('Everything Vitafolio does: several CVs per person, privacy for each, uploads, LaTeX, sharing, private analytics, Check a CV, jobs and an application tracker.')">
    <div class="container-page py-12">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-brass-text">{{ __('Features') }}</p>
            <h1 class="mt-2 text-4xl sm:text-5xl">{{ __('Everything a CV needs. Nothing it does not.') }}</h1>
            <p class="mt-4 text-lg text-muted">{{ __('Vitafolio is free, has no adverts and never sells your data.') }}</p>
        </div>

        <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as [$title, $text])
                <li class="card p-6">
                    <h2 class="text-xl">{{ $title }}</h2>
                    <p class="mt-2 text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ul>

        <section id="demo" aria-labelledby="demo-title" class="mt-20 scroll-mt-24">
            <h2 id="demo-title" class="text-3xl">{{ __('See it in action') }}</h2>
            <p class="mt-2 max-w-2xl text-muted">{{ __('Short clips recorded with made-up people. Select a clip to watch it at full size.') }}</p>
            <ul class="mt-8 grid gap-5 lg:grid-cols-3">
                @foreach (\App\Support\Demos::all() as $clip => $demo)
                    <li class="card overflow-hidden">
                        <a href="{{ route('features.demo', $clip) }}" class="block">
                            <x-demo-clip :clip="$clip" :alt="$demo['alt']" />
                            <span class="sr-only">{{ __('Watch :title at full size', ['title' => $demo['title']]) }}</span>
                        </a>
                        <div class="p-6">
                            <h3 class="text-xl">{{ $demo['title'] }}</h3>
                            <p class="mt-2 text-muted">{{ $demo['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="how-title" class="mt-20">
            <h2 id="how-title" class="text-3xl">{{ __('How it works') }}</h2>
            <ol class="mt-8 grid gap-5 md:grid-cols-3">
                @foreach ($steps as $index => [$title, $text])
                    <li class="card p-6">
                        <span class="inline-flex size-10 items-center justify-center rounded-full bg-brand text-lg font-bold text-on-brand" aria-hidden="true">{{ $index + 1 }}</span>
                        <h3 class="mt-4 text-xl">{{ $title }}</h3>
                        <p class="mt-2 text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="card mt-20 flex flex-wrap items-center justify-between gap-6 p-8">
            <div>
                <h2 class="text-2xl">{{ __('Ready to start?') }}</h2>
                <p class="mt-1 text-muted">{{ __('It takes a minute. Your first CV is waiting.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a class="btn btn-primary" href="{{ route('register') }}">{{ __('Create your CV') }}</a>
                <a class="btn btn-secondary" href="{{ route('help') }}">{{ __('Read the guides') }}</a>
            </div>
        </section>
    </div>
</x-layouts.app>
