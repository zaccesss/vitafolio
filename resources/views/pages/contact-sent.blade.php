<x-layouts.app title="Message sent" noindex>
    <div class="container-page py-16">
        <div class="card mx-auto max-w-xl p-8 text-center">
            <h1 class="text-3xl">Message sent</h1>
            <p class="mt-3 text-lg text-muted">Thanks for getting in touch. Replies usually arrive within a few working days, by email to the address you gave.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a class="btn btn-primary" href="{{ route('help') }}">Browse the help centre</a>
                <a class="btn btn-secondary" href="{{ route('home') }}">Back to the home page</a>
            </div>
        </div>
    </div>
</x-layouts.app>
