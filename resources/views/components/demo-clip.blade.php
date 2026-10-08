@props(['clip', 'alt'])
{{-- animated images rather than video: they play without scripts under the strict content security policy.
     The reduced motion source swaps each for a still frame and the theme picks the light or dark recording --}}
@foreach (['' => 'dark:hidden', '-dark' => 'hidden dark:block'] as $suffix => $visibility)
    <picture class="{{ $visibility }}">
        <source media="(prefers-reduced-motion: reduce)" srcset="{{ asset('demo/'.$clip.$suffix.'-still.webp') }}">
        <img src="{{ asset('demo/'.$clip.$suffix.'.webp') }}" alt="{{ $alt }}" width="960" height="600" loading="lazy" decoding="async" class="aspect-[8/5] w-full border-b border-line bg-page object-cover">
    </picture>
@endforeach
