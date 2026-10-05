@props(['items'])
{{-- each item is [label, url]; the last is the current page and has no url. the structured
     copy lets search engines show the trail in results; it is a data block, never executed --}}
@php
    $trail = [[__('Home'), route('home')], ...$items];
@endphp
<nav aria-label="Breadcrumb" class="text-sm text-muted no-print">
    <ol class="flex flex-wrap gap-2">
        @foreach ($trail as [$label, $url])
            <li>
                @if (! $loop->last && $url)
                    <a href="{{ $url }}">{{ $label }}</a> <span aria-hidden="true">/</span>
                @else
                    <span aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($trail)->values()->map(fn ($item, $i) => array_filter([
        '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1] ?? null,
    ]))->all(),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
