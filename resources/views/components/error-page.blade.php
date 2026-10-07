@props(['code', 'title', 'message'])
{{-- no session, database or signed-in user here: an error page has to render even when those are what failed.
     the language comes from the page's own choice when it got that far, otherwise from the browser --}}
@php
    $errorLocale = request()->attributes->get('locale') ?? \App\Support\Locales::fromHeader(request()->header('Accept-Language'));
    app()->setLocale($errorLocale);
@endphp
<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::html($errorLocale) }}" dir="{{ \App\Support\Locales::dir($errorLocale) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __($title) }} | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="{{ asset('theme-init.js') }}"></script>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-dvh flex-col">
    <main id="main" class="container-page flex max-w-xl flex-1 flex-col items-center justify-center py-16 text-center">
        <a href="{{ url('/') }}" class="flex items-center gap-3 no-underline">
            <x-brand-mark :size="40" />
            <span class="text-xl font-semibold text-ink">{{ config('app.name') }}</span>
        </a>
        <p class="mt-10 font-mono text-sm tracking-widest text-brass-text">{{ __('Error :code', ['code' => $code]) }}</p>
        <h1 class="mt-2 text-3xl">{{ __($title) }}</h1>
        <p class="mt-3 text-muted">{{ __($message) }}</p>
        {{ $slot }}
        <a class="btn btn-primary mt-8" href="{{ url('/') }}">{{ __('Go to the home page') }}</a>
    </main>
</body>
</html>
