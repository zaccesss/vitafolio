@php
    $features = [
        ['Every version of your CV', 'Keep up to ten named CVs, one for each kind of role. Duplicate one to start the next version in seconds.'],
        ['You choose who sees each one', 'Make each CV public, unlisted or private. Public CVs appear in the directory; unlisted ones open only for people with the link.'],
        ['Bring the CV you already have', 'Upload a PDF or Word file, import JSON Resume or write it in LaTeX and compile it in your browser.'],
        ['Show your work', 'Add projects with links, images and short videos, each described for people who cannot see them.'],
        ['Share it anywhere', 'A clean link, a QR code for printed copies, a polished PDF and a preview card when the link is posted.'],
        ['Know what is working', 'See views, PDF downloads, QR scans and where visitors came from, counted without tracking anyone.'],
        ['Hear from employers', 'Visitors can message you from your CV without seeing your email address.'],
        ['Safe by design', 'Passkeys, two-factor authentication, breach-checked passwords and a strict security policy on every page.'],
        ['Built for everyone', 'Readable in light and dark mode, usable with a keyboard or screen reader, with a plain theme for applicant tracking systems.'],
    ];
    $steps = [
        ['Create your account', 'Sign up with your email or with Google, GitHub or Microsoft. Your first CV is ready straight away.'],
        ['Build or upload', 'Fill in your sections, add projects and pick a theme. Or upload the file you already have.'],
        ['Share', 'Choose who can see it, then share the link, the QR code or the PDF.'],
    ];
@endphp
<x-layouts.app title="Features" description="Everything Vitafolio does: several CVs per person, privacy for each, uploads, LaTeX, sharing and private analytics.">
    <div class="container-page py-12">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-brass-text">Features</p>
            <h1 class="mt-2 text-4xl sm:text-5xl">Everything a CV needs. Nothing it does not.</h1>
            <p class="mt-4 text-lg text-muted">Vitafolio is free, has no adverts and never sells your data.</p>
        </div>

        <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as [$title, $text])
                <li class="card p-6">
                    <h2 class="text-xl">{{ $title }}</h2>
                    <p class="mt-2 text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ul>

        <section aria-labelledby="how-title" class="mt-20">
            <h2 id="how-title" class="text-3xl">How it works</h2>
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
                <h2 class="text-2xl">Ready to start?</h2>
                <p class="mt-1 text-muted">It takes a minute. Your first CV is waiting.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a class="btn btn-primary" href="{{ route('register') }}">Create your CV</a>
                <a class="btn btn-secondary" href="{{ route('help') }}">Read the guides</a>
            </div>
        </section>
    </div>
</x-layouts.app>
