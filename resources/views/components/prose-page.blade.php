@props(['title', 'intro' => null, 'updated' => null, 'englishOnly' => false])
<x-layouts.app :title="$title" >
    <div class="container-page py-8">
        <x-breadcrumbs :items="[[$title, null]]" />
        @if ($englishOnly)<x-english-only class="mt-6 max-w-3xl" />@endif
        <article data-reading @if ($englishOnly) lang="en-GB" dir="ltr" @endif class="mt-6 prose prose-lg max-w-3xl prose-headings:font-semibold prose-headings:text-ink prose-p:text-ink prose-li:text-ink prose-a:text-link prose-strong:text-ink dark:prose-invert">
            <h1>{{ $title }}</h1>
            @if ($intro)<p class="lead !text-muted">{{ $intro }}</p>@endif
            @if ($updated)<p class="text-sm !text-muted">{{ $englishOnly ? 'Last updated '.$updated : __('Last updated :date', ['date' => $updated]) }}</p>@endif
            {{ $slot }}
        </article>
    </div>
</x-layouts.app>
