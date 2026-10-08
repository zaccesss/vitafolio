@props(['slug', 'demo' => null])
@php
    [$title, $summary] = array_map('__', \App\Support\HelpTopics::ALL[$slug]);
    $slugs = array_keys(\App\Support\HelpTopics::ALL);
    $position = array_search($slug, $slugs, true);
    $previous = $slugs[$position - 1] ?? null;
    $next = $slugs[$position + 1] ?? null;
@endphp
<x-layouts.app :title="$title.' | '.__('Help')" :description="$summary">
    <div class="container-page py-8">
        <x-breadcrumbs :items="[[__('Help centre'), route('help')], [$title, null]]" />
        <div class="mt-6 grid gap-10 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <nav aria-label="{{ __('Help topics') }}" class="lg:sticky lg:top-24 lg:self-start">
                <ul>
                    @foreach (\App\Support\HelpTopics::ALL as $key => [$label])
                        <li><a class="settings-link" href="{{ route('help.topic', $key) }}" @if($key === $slug) aria-current="page" @endif>{{ __($label) }}</a></li>
                    @endforeach
                    <li class="mt-3 border-t border-line pt-3"><a class="settings-link" href="{{ route('contact.show') }}">{{ __('Contact us') }}</a></li>
                </ul>
            </nav>
            <article data-reading class="prose prose-lg max-w-3xl prose-headings:font-semibold prose-headings:text-ink prose-p:text-ink prose-li:text-ink prose-a:text-link prose-strong:text-ink dark:prose-invert">
                <h1>{{ $title }}</h1>
                <p class="lead !text-muted">{{ $summary }}</p>
                @if ($demo)
                    @php($clip = \App\Support\Demos::all()[$demo])
                    <figure class="not-prose card my-8 overflow-hidden">
                        <a href="{{ route('features.demo', $demo) }}" class="block">
                            <x-demo-clip :clip="$demo" :alt="$clip['alt']" />
                            <span class="sr-only">{{ __('Watch :title at full size', ['title' => $clip['title']]) }}</span>
                        </a>
                        <figcaption class="p-4 text-sm text-muted">{{ $clip['text'] }} <a href="{{ route('features.demo', $demo) }}">{{ __('Watch :title at full size', ['title' => $clip['title']]) }}</a></figcaption>
                    </figure>
                @endif
                {{ $slot }}
                <nav aria-label="{{ __('More help') }}" class="not-prose mt-12 flex flex-wrap justify-between gap-3 border-t border-line pt-6">
                    @if ($previous)<a class="btn btn-secondary" href="{{ route('help.topic', $previous) }}"><span aria-hidden="true" class="inline-block rtl:-scale-x-100">&larr;</span> {{ __(\App\Support\HelpTopics::ALL[$previous][0]) }}</a>@else<span></span>@endif
                    @if ($next)<a class="btn btn-secondary" href="{{ route('help.topic', $next) }}">{{ __(\App\Support\HelpTopics::ALL[$next][0]) }} <span aria-hidden="true" class="inline-block rtl:-scale-x-100">&rarr;</span></a>@endif
                </nav>
            </article>
        </div>
    </div>
</x-layouts.app>
