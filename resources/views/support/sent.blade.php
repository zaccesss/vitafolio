<x-layouts.app :title="__('Ticket opened')" noindex>
    <div class="container-page max-w-2xl py-12">
        <h1 class="text-3xl">{{ __('Your ticket is open') }}</h1>
        @if (session('reference'))
            <p class="mt-3 text-lg">{{ __('Your reference is :ref.', ['ref' => session('reference')]) }}</p>
        @endif
        <p class="mt-3 text-muted">{{ __('We have emailed you a private link to the ticket. Use it to read replies and add to the conversation. If it does not arrive, check your spam folder.') }}</p>
        <p class="mt-6"><a href="{{ route('support') }}">{{ __('Back to support') }}</a></p>
    </div>
</x-layouts.app>
