@props(['title', 'intro' => null, 'updated' => null])
<x-layouts.app :title="$title" :turnstile="request()->routeIs('about')">
    <div class="container-page py-12">
        <article class="prose prose-lg max-w-3xl prose-headings:font-semibold prose-headings:text-ink prose-p:text-ink prose-li:text-ink prose-a:text-link prose-strong:text-ink dark:prose-invert">
            <h1>{{ $title }}</h1>
            @if ($intro)<p class="lead !text-muted">{{ $intro }}</p>@endif
            @if ($updated)<p class="text-sm !text-muted">Last updated {{ $updated }}</p>@endif
            {{ $slot }}
        </article>
    </div>
</x-layouts.app>
