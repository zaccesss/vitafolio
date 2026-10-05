@php
    $routes = array_filter([
        ['Help centre', 'Guides for every part of the site, from your first CV to passkeys, with a search box.', route('help'), 'Open the help centre'],
        ['Frequently asked questions', 'Short answers to the questions people ask most.', route('help.topic', 'faq'), 'Read the FAQ'],
        ['Contact', 'Send a message and get a reply by email, usually within a few working days.', route('contact.show'), 'Go to the contact page'],
        config('vitafolio.status_url') ? ['Service status', 'Is the site up? Check the status page and its history of incidents.', config('vitafolio.status_url'), 'Open the status page'] : null,
        ['Report a CV', 'A CV that breaks the rules can be reported anonymously with the Report button at the bottom of that CV.', route('terms').'#rules', 'Read the rules'],
        ['Security problems', 'Report a vulnerability privately so it can be fixed before anyone else learns of it.', url('/.well-known/security.txt'), 'How to report one'],
        ['Accessibility', 'What the site does for screen readers, keyboards and low vision. How to report a barrier.', route('accessibility'), 'Read the statement'],
        ['Your data', 'Download everything stored about you or delete your account, any time.', route('settings.data'), 'Open your data settings'],
    ]);
@endphp
<x-layouts.app title="Support" description="Where to get help with Vitafolio: guides, contact, service status, reporting and accessibility.">
    <div class="container-page py-12">
        <div class="max-w-2xl">
            <h1 class="text-4xl">Support</h1>
            <p class="mt-3 text-lg text-muted">Everything here is free, including support. Pick the route that fits.</p>
        </div>
        <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($routes as [$title, $text, $href, $label])
                <li class="card flex flex-col p-6">
                    <h2 class="text-xl">{{ $title }}</h2>
                    <p class="mt-2 flex-1 text-muted">{{ $text }}</p>
                    <a class="mt-4 font-semibold" href="{{ $href }}" @if(! str_starts_with($href, url('/'))) rel="noopener" @endif>{{ $label }}</a>
                </li>
            @endforeach
        </ul>
        <p class="mt-10 text-sm text-muted">Never include a password or a sign-in code in any message. Nobody who works on {{ config('app.name') }} will ever ask for one.</p>
    </div>
</x-layouts.app>
