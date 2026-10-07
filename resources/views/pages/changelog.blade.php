@php
    // the public changelog is the repository's own CHANGELOG.md, so the two can never disagree
    $path = base_path('CHANGELOG.md');
    $html = is_file($path) ? Str::markdown((string) file_get_contents($path), ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '';
@endphp
<x-layouts.app title="What is new" description="Every notable change to Vitafolio, newest first.">
    <div class="container-page py-12">
        <x-english-only class="mb-6 max-w-3xl" />
        <article data-reading lang="en-GB" dir="ltr" class="prose prose-lg max-w-3xl prose-headings:font-semibold prose-headings:text-ink prose-p:text-ink prose-li:text-ink prose-a:text-link prose-strong:text-ink dark:prose-invert">
            {!! $html ?: '<h1>What is new</h1><p>Nothing to show yet.</p>' !!}
        </article>
    </div>
</x-layouts.app>
