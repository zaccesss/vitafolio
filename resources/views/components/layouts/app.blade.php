@props([
    'title' => null,
    'description' => 'Build, store and share CVs. Keep several versions, choose who sees each one and share it with a single link.',
    'canonical' => null,
    'image' => null,
    'imageAlt' => null,
    'noindex' => false,
    'turnstile' => false,
    'type' => 'website',
])
@php
    $siteName = config('app.name');
    $fullTitle = $title ? $title.' | '.$siteName : $siteName.': build, store and share your CVs';
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="en-GB" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    @if ($noindex)<meta name="robots" content="noindex">@endif
    @if ($canonical)<link rel="canonical" href="{{ $canonical }}">@endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="{{ $type }}">
    @if ($canonical)<meta property="og:url" content="{{ $canonical }}">@endif
    <meta property="og:image" content="{{ $image ?? asset('og-default.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $imageAlt ?? $siteName.': every version of your CV, in one place.' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#14213d" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0b1220" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ route('manifest') }}">
    {{-- runs before paint so a dark theme never flashes white --}}
    <script src="{{ asset('theme-init.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($turnstile && \App\Rules\Turnstile::enabled())
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    @if (config('vitafolio.analytics_token'))
        <script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{"token":"{{ config('vitafolio.analytics_token') }}"}'></script>
    @endif
    {{ $head ?? '' }}
</head>
<body class="flex min-h-dvh flex-col">
    <a class="skip-link" href="#main">Skip to main content</a>
    <div id="page-progress" class="page-progress" role="progressbar" aria-label="Loading the next page" aria-hidden="true" hidden></div>

    @php
        $mainLinks = array_filter([
            ['home', 'Browse CVs', ['home']],
            Route::has('features') ? ['features', 'Features', ['features']] : null,
            Route::has('help') ? ['help', 'Help', ['help', 'help.*']] : null,
        ]);
        $menuLinks = $user ? array_filter([
            ['dashboard', 'My CVs', ['dashboard', 'cvs.*']],
            Route::has('analytics') ? ['analytics', 'Analytics', ['analytics']] : null,
            ['profile.edit', 'Settings', ['profile.*', 'account*', 'settings.*']],
            $user->isAdmin() ? ['admin.index', 'Admin', ['admin.*']] : null,
        ]) : [];
    @endphp
    {{-- full width, so the logo and the theme toggle sit in the corners on every screen size --}}
    <header class="site-header sticky top-0 z-50 bg-header text-white no-print" x-data="menu" @keydown.escape.window="close">
        <div class="flex min-h-16 items-center gap-2 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="mr-4 inline-flex shrink-0 items-center gap-2.5 text-xl font-bold tracking-tight text-white no-underline">
                <x-brand-mark /><span>{{ $siteName }}</span>
            </a>

            <nav aria-label="Main" class="hidden md:block">
                <ul class="flex items-center gap-1">
                    @foreach ($mainLinks as [$name, $label, $active])
                        <li><a class="nav-link" href="{{ route($name) }}" @if(request()->routeIs(...$active)) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div class="ml-auto flex items-center gap-1">
                @auth
                    <div class="relative hidden md:block" x-data="accountMenu" @keydown.escape.window="close" @click.outside="close">
                        <button type="button" class="nav-link inline-flex items-center gap-2 py-1.5" aria-controls="account-menu" :aria-expanded="expanded" @click="toggle">
                            <x-avatar :user="$user" size="xs" />
                            <span class="max-w-40 truncate">{{ $user->firstName() }}</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                            <span class="sr-only">Account menu</span>
                        </button>
                        <div id="account-menu" class="account-menu" :hidden="closed" hidden>
                            <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                                <x-avatar :user="$user" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-ink">{{ $user->name }}</p>
                                    <p class="truncate text-sm text-muted">&#64;{{ $user->handle }}</p>
                                </div>
                            </div>
                            <ul class="py-2">
                                <li><a class="menu-link" href="{{ route('profile.show', $user->handle) }}">View your public profile</a></li>
                                @foreach ($menuLinks as [$name, $label, $active])
                                    <li><a class="menu-link" href="{{ route($name) }}" @if(request()->routeIs(...$active)) aria-current="page" @endif>{{ $label }}</a></li>
                                @endforeach
                            </ul>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-line py-2">
                                @csrf
                                <button type="submit" class="menu-link w-full cursor-pointer text-left">Sign out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a class="nav-link hidden md:inline-flex" href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Sign in</a>
                    <a class="btn btn-sm btn-on-dark hidden md:inline-flex" href="{{ route('register') }}">Create your CV</a>
                @endauth
                <x-theme-toggle />
                <button type="button" class="ml-1 inline-flex size-11 items-center justify-center rounded-lg border-2 border-white/60 md:hidden"
                        aria-controls="mobile-nav" :aria-expanded="expanded" @click="toggle">
                    <span class="sr-only">Menu</span>
                    <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>

        {{-- phones get one panel with everything; without javascript it stays open --}}
        <nav id="mobile-nav" aria-label="Main" class="border-t border-white/15 px-4 pb-4 md:hidden" :class="navClass">
            <ul class="flex flex-col gap-1 pt-2">
                @foreach ($mainLinks as [$name, $label, $active])
                    <li><a class="nav-link block" href="{{ route($name) }}" @if(request()->routeIs(...$active)) aria-current="page" @endif>{{ $label }}</a></li>
                @endforeach
                @auth
                    <li class="mt-2 border-t border-white/15 pt-3 text-sm text-white/80">Signed in as <strong class="text-white">&#64;{{ $user->handle }}</strong></li>
                    <li><a class="nav-link block" href="{{ route('profile.show', $user->handle) }}">View your public profile</a></li>
                    @foreach ($menuLinks as [$name, $label, $active])
                        <li><a class="nav-link block" href="{{ route($name) }}" @if(request()->routeIs(...$active)) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="nav-link block w-full cursor-pointer text-left">Sign out</button></form>
                    </li>
                @else
                    <li><a class="nav-link block" href="{{ route('login') }}">Sign in</a></li>
                    <li class="pt-2"><a class="btn btn-on-dark w-full" href="{{ route('register') }}">Create your CV</a></li>
                @endauth
            </ul>
        </nav>
    </header>

    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
        <x-toasts />
        {{ $slot }}
    </main>

    @php
        $footer = [
            'Product' => array_filter([
                ['Browse CVs', route('home')],
                Route::has('features') ? ['Features', route('features')] : null,
                ['Create a CV', route('register')],
                Route::has('changelog') ? ['What is new', route('changelog')] : null,
                Route::has('docs') ? ['Documentation', route('docs')] : null,
            ]),
            'Help' => array_filter([
                Route::has('support') ? ['Support', route('support')] : null,
                Route::has('help') ? ['Help centre', route('help')] : null,
                Route::has('help') ? ['Frequently asked questions', route('help.topic', 'faq')] : null,
                ['Contact', Route::has('contact.show') ? route('contact.show') : route('about').'#contact'],
                ['About', route('about')],
            ]),
            'Legal' => [
                ['Privacy policy', route('privacy')],
                ['Terms of use', route('terms')],
                ['Cookies', route('cookies')],
                ['Accessibility', route('accessibility')],
            ],
            'More' => array_filter([
                ['Report a security issue', url('/.well-known/security.txt')],
                config('vitafolio.status_url') ? ['Service status', config('vitafolio.status_url')] : null,
                config('vitafolio.source_url') ? ['Source code', config('vitafolio.source_url')] : null,
                ['Sitemap', route('sitemap')],
            ]),
        ];
    @endphp
    <footer class="site-footer mt-16 border-t border-line bg-surface no-print">
        <div class="grid gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-6 lg:px-8">
            <div class="sm:col-span-2">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-bold text-ink no-underline"><x-brand-mark :size="28" />{{ $siteName }}</a>
                <p class="mt-3 max-w-sm text-sm text-muted">Every version of your CV, in one place. Free to use, with no adverts and no tracking.</p>
                <p class="mt-2 max-w-sm text-xs text-muted">Not affiliated with any university or employer named on this site.</p>
            </div>
            @foreach ($footer as $heading => $links)
                <nav aria-labelledby="footer-{{ Str::slug($heading) }}">
                    <h2 id="footer-{{ Str::slug($heading) }}" class="text-sm font-semibold uppercase tracking-wider text-muted">{{ $heading }}</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($links as [$label, $href])
                            <li><a href="{{ $href }}" @if(! str_starts_with($href, url('/'))) rel="noopener" @endif>{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>
        <div class="border-t border-line">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-5 text-sm text-muted sm:px-6 lg:px-8">
                <p>
                    &copy; {{ date('Y') }}
                    @if (config('vitafolio.owner.url'))
                        <a href="{{ config('vitafolio.owner.url') }}" rel="noopener">{{ config('vitafolio.owner.name') }}</a>.
                    @else
                        {{ config('vitafolio.owner.name') }}.
                    @endif
                    All rights reserved.
                </p>
            </div>
        </div>
    </footer>
    <div id="reading-progress" class="reading-progress no-print" aria-hidden="true" hidden></div>
    <button type="button" id="back-to-top" class="back-to-top no-print" hidden><span aria-hidden="true">&uarr;</span><span class="sr-only">Back to top</span></button>
    <p id="copy-status" class="sr-only" role="status"></p>
    <dialog id="confirm-dialog" class="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-text">
        <h2 id="confirm-title" class="text-xl">Are you sure?</h2>
        <p id="confirm-text" class="mt-2 text-muted"></p>
        <form method="dialog" class="mt-6 flex flex-wrap justify-end gap-3">
            <button value="cancel" class="btn btn-secondary" autofocus>Cancel</button>
            <button value="ok" class="btn btn-danger">Yes, continue</button>
        </form>
    </dialog>
</body>
</html>
