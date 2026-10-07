@props(['title', 'intro' => null])
@php
    $user = auth()->user();
    $groups = [
        __('Profile') => [
            ['profile.edit', __('Public profile')],
            ['settings.photo', __('Photo')],
            ['settings.handle', __('Handle')],
        ],
        __('Account') => [
            ['account', __('Name and email')],
            ['settings.security', __('Password and two-factor')],
            ['settings.passkeys', __('Passkeys')],
            ['settings.connected', __('Connected accounts')],
            ['settings.sessions', __('Signed-in devices')],
            ['settings.data', __('Your data')],
        ],
    ];
@endphp
<x-layouts.app :title="$title.' | '.__('Settings')" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-muted">{{ __('Settings') }}</p>
                <h1 class="text-3xl">{{ $title }}</h1>
                @if ($intro)<p class="mt-1 text-muted">{{ $intro }}</p>@endif
            </div>
            <a class="btn btn-secondary" href="{{ route('profile.show', $user->handle) }}">{{ __('View your profile') }}</a>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <nav aria-label="{{ __('Settings') }}" class="settings-nav">
                @foreach ($groups as $heading => $links)
                    <h2 class="px-3 text-xs font-semibold uppercase tracking-wider text-muted">{{ $heading }}</h2>
                    <ul class="mb-4 mt-1">
                        @foreach ($links as [$name, $label])
                            <li><a class="settings-link" href="{{ route($name) }}" @if(request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a></li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>
            <div class="grid min-w-0 content-start gap-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
