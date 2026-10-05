@php
    // the technical documentation is the repository's own docs/DOCUMENTATION.md, rendered as is
    $path = base_path('docs/DOCUMENTATION.md');
    $markdown = is_file($path) ? (string) file_get_contents($path) : '';
    // links written for github are made to work from the site
    $markdown = str_replace(['](../', '](#'], ['](' . rtrim((string) config('vitafolio.source_url'), '/') . '/blob/main/', '](#'], $markdown);
    $html = $markdown ? Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '';
@endphp
<x-layouts.app title="Documentation" description="How Vitafolio is built, how it keeps data safe and how to run your own copy.">
    <div class="container-page py-8">
        <x-breadcrumbs :items="[['Documentation', null]]" />
        <article data-reading class="mt-6 prose prose-lg max-w-4xl prose-headings:font-semibold prose-headings:text-ink prose-p:text-ink prose-li:text-ink prose-a:text-link prose-strong:text-ink prose-th:text-ink prose-td:text-ink dark:prose-invert">
            {!! $html ?: '<h1>Documentation</h1><p>The documentation is not available on this copy of the site.</p>' !!}
        </article>
    </div>
</x-layouts.app>
