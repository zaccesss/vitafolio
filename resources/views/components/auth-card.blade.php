@props(['title', 'intro' => null, 'turnstile' => false])
<x-layouts.app :title="$title" noindex :turnstile="$turnstile">
    <div class="container-page py-12 sm:py-16">
        <div class="mx-auto w-full max-w-md">
            <div class="card p-7 sm:p-8">
                <h1 class="text-2xl">{{ $title }}</h1>
                @if ($intro)<p class="mt-2 text-muted">{{ $intro }}</p>@endif
                <div class="mt-6">
                    <x-error-summary />
                    {{ $slot }}
                </div>
            </div>
            @isset($footer)<div class="mt-6 text-center text-sm text-muted">{{ $footer }}</div>@endisset
        </div>
    </div>
</x-layouts.app>
