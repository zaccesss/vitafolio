@props(['title', 'intro' => null])
@php
    $user = auth()->user();
    $groups = [
        'Profile' => [
            ['profile.edit', 'Public profile'],
            ['settings.photo', 'Photo'],
            ['settings.handle', 'Handle'],
        ],
        'Account' => [
            ['account', 'Name and email'],
            ['settings.security', 'Password and two-factor'],
            ['settings.passkeys', 'Passkeys'],
            ['settings.connected', 'Connected accounts'],
            ['settings.sessions', 'Signed-in devices'],
            ['settings.data', 'Your data'],
        ],
    ];
@endphp
<x-layouts.app :title="$title.' | Settings'" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-muted">Settings</p>
                <h1 class="text-3xl">{{ $title }}</h1>
                @if ($intro)<p class="mt-1 text-muted">{{ $intro }}</p>@endif
            </div>
            <a class="btn btn-secondary" href="{{ route('profile.show', $user->handle) }}">View your profile</a>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <nav aria-label="Settings" class="settings-nav">
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
