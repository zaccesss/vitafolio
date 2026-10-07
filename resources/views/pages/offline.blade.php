<x-layouts.app :title="__('You are offline')" noindex>
    <div class="container-page max-w-xl py-16 text-center">
        <x-brand-mark :size="56" class="mx-auto" />
        <h1 class="mt-6 text-3xl">{{ __('You are offline') }}</h1>
        <p class="mt-3 text-muted">{{ __('This page needs a connection. Check your Wi-Fi or mobile data, then try again.') }}</p>
        <p class="mt-2 text-sm text-muted">{{ __('Nothing you were working on is stored on this device, so nothing is lost by closing the page.') }}</p>
        {{-- the worker shows this page in place of whatever failed to load, so reloading retries that page --}}
        <a class="btn btn-primary mt-8" href="{{ route('home') }}" data-reload>{{ __('Try again') }}</a>
    </div>
</x-layouts.app>
