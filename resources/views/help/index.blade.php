<x-layouts.app title="Help centre" description="Guides for building, sharing and protecting your CVs on Vitafolio.">
    <div class="container-page py-10" x-data="helpSearch">
        <div class="max-w-2xl">
            <h1 class="text-4xl">Help centre</h1>
            <p class="mt-3 text-lg text-muted">Guides for building, sharing and protecting your CVs.</p>
            <label for="help-search" class="field-label mt-6 block">Search the help centre</label>
            <input id="help-search" type="search" class="input mt-1" placeholder="For example: handle, PDF, passkey" x-model="query" autocomplete="off">
            <p class="sr-only" role="status" aria-live="polite" x-text="announcement"></p>
        </div>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (\App\Support\HelpTopics::ALL as $slug => [$title, $summary])
                <li data-help-topic="{{ Str::lower($title.' '.$summary) }}">
                    <a href="{{ route('help.topic', $slug) }}" class="card card-hover block h-full p-5 no-underline">
                        <h2 class="text-lg text-ink">{{ $title }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ $summary }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="mt-10 text-muted">Cannot find what you need? <a href="{{ route('contact.show') }}">Contact us</a>.</p>
    </div>
</x-layouts.app>
