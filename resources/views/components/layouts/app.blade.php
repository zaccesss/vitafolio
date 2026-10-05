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
<body class="flex min-h-screen flex-col">
    <a class="skip-link" href="#main">Skip to main content</a>

    <header class="site-header sticky top-0 z-50 bg-header text-white no-print" x-data="menu">
        <div class="container-page flex min-h-16 flex-wrap items-center justify-between gap-x-4 py-2">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 text-xl font-bold tracking-tight text-white no-underline">
                <x-brand-mark /><span>{{ $siteName }}</span>
            </a>

            <button type="button" class="inline-flex size-11 items-center justify-center rounded-lg border-2 border-white/60 md:hidden"
                    aria-controls="site-nav" :aria-expanded="expanded" @click="toggle">
                <span class="sr-only">Menu</span>
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>

            <nav id="site-nav" aria-label="Main" class="w-full md:block md:w-auto" :class="navClass">
                <ul class="flex flex-col gap-1 py-2 md:flex-row md:items-center md:py-0">
                    <li><a class="nav-link block" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Browse CVs</a></li>
                    <li><a class="nav-link block" href="{{ route('about') }}" @if(request()->routeIs('about')) aria-current="page" @endif>About</a></li>
                    @auth
                        <li><a class="nav-link block" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard', 'cvs.*')) aria-current="page" @endif>My CVs</a></li>
                        <li><a class="nav-link block" href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>Profile</a></li>
                        <li><a class="nav-link block" href="{{ route('account') }}" @if(request()->routeIs('account')) aria-current="page" @endif>Account</a></li>
                        @can('admin')
                            <li><a class="nav-link block" href="{{ route('admin.index') }}" @if(request()->routeIs('admin.*')) aria-current="page" @endif>Admin</a></li>
                        @endcan
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-link block w-full cursor-pointer text-left">Sign out</button>
                            </form>
                        </li>
                    @else
                        <li><a class="nav-link block" href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Sign in</a></li>
                        <li class="md:ml-1"><a class="btn btn-sm btn-on-dark" href="{{ route('register') }}">Create your CV</a></li>
                    @endauth
                    <li class="md:ml-1"><x-theme-toggle /></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
        <x-toasts />
        {{ $slot }}
    </main>

    <footer class="site-footer mt-16 border-t border-line bg-surface no-print">
        <div class="container-page grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-bold text-ink no-underline"><x-brand-mark :size="28" />{{ $siteName }}</a>
                <p class="mt-3 text-sm text-muted">Build, store and share your CVs. Not affiliated with any university or employer listed here.</p>
            </div>
            <nav aria-labelledby="footer-product">
                <h2 id="footer-product" class="text-sm font-semibold uppercase tracking-wider text-muted">Product</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('home') }}">Browse CVs</a></li>
                    <li><a href="{{ route('register') }}">Create a CV</a></li>
                    <li><a href="{{ route('about') }}">About</a></li>
                    <li><a href="{{ route('about') }}#contact">Contact</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="footer-legal">
                <h2 id="footer-legal" class="text-sm font-semibold uppercase tracking-wider text-muted">Your data</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('privacy') }}">Privacy</a></li>
                    <li><a href="{{ route('cookies') }}">Cookies</a></li>
                    <li><a href="{{ route('terms') }}">Terms</a></li>
                    <li><a href="{{ route('accessibility') }}">Accessibility</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="footer-more">
                <h2 id="footer-more" class="text-sm font-semibold uppercase tracking-wider text-muted">More</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ url('/.well-known/security.txt') }}">Report a security issue</a></li>
                    @if (config('vitafolio.source_url'))<li><a href="{{ config('vitafolio.source_url') }}" rel="noopener">Source code</a></li>@endif
                    <li><a href="{{ route('sitemap') }}">Sitemap</a></li>
                </ul>
            </nav>
        </div>
        <div class="border-t border-line">
            <p class="container-page py-5 text-sm text-muted">
                &copy; {{ date('Y') }}
                @if (config('vitafolio.owner.url'))
                    <a href="{{ config('vitafolio.owner.url') }}" rel="noopener">{{ config('vitafolio.owner.name') }}</a>
                @else
                    {{ config('vitafolio.owner.name') }}
                @endif
            </p>
        </div>
    </footer>
</body>
</html>
