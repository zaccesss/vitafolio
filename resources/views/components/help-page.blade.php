@props(['slug'])
@php
    [$title, $summary] = \App\Support\HelpTopics::ALL[$slug];
    $slugs = array_keys(\App\Support\HelpTopics::ALL);
    $position = array_search($slug, $slugs, true);
    $previous = $slugs[$position - 1] ?? null;
    $next = $slugs[$position + 1] ?? null;
@endphp
<x-layouts.app :title="$title.' | Help'" :description="$summary">
    <div class="container-page py-8">
        <nav aria-label="Breadcrumb" class="text-sm text-muted">
            <ol class="flex flex-wrap gap-2">
                <li><a href="{{ route('help') }}">Help centre</a> <span aria-hidden="true">/</span></li>
                <li aria-current="page">{{ $title }}</li>
            </ol>
        </nav>
        <div class="mt-6 grid gap-10 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <nav aria-label="Help topics" class="lg:sticky lg:top-24 lg:self-start">
                <ul>
                    @foreach (\App\Support\HelpTopics::ALL as $key => [$label])
                        <li><a class="settings-link" href="{{ route('help.topic', $key) }}" @if($key === $slug) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                    <li class="mt-3 border-t border-line pt-3"><a class="settings-link" href="{{ route('contact.show') }}">Contact us</a></li>
                </ul>
            </nav>
            <article class="prose prose-lg max-w-3xl prose-headings:font-semibold prose-headings:text-ink prose-p:text-ink prose-li:text-ink prose-a:text-link prose-strong:text-ink dark:prose-invert">
                <h1>{{ $title }}</h1>
                <p class="lead !text-muted">{{ $summary }}</p>
                {{ $slot }}
                <nav aria-label="More help" class="not-prose mt-12 flex flex-wrap justify-between gap-3 border-t border-line pt-6">
                    @if ($previous)<a class="btn btn-secondary" href="{{ route('help.topic', $previous) }}"><span aria-hidden="true">&larr;</span> {{ \App\Support\HelpTopics::ALL[$previous][0] }}</a>@else<span></span>@endif
                    @if ($next)<a class="btn btn-secondary" href="{{ route('help.topic', $next) }}">{{ \App\Support\HelpTopics::ALL[$next][0] }} <span aria-hidden="true">&rarr;</span></a>@endif
                </nav>
            </article>
        </div>
    </div>
</x-layouts.app>
