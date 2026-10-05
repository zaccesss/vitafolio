@props(['user', 'size' => 'md', 'accent' => null])
@php
    $sizes = ['sm' => 'size-10 text-sm', 'md' => 'size-16 text-xl', 'lg' => 'size-28 text-4xl'];
    $classes = 'shrink-0 rounded-full '.$sizes[$size];
@endphp
@if ($user->hasAvatar())
    {{-- decorative beside the visible name, so screen readers skip it --}}
    <img src="{{ $user->avatarUrl() }}" alt="" width="112" height="112" loading="lazy" decoding="async" class="{{ $classes }} bg-raised object-cover">
@else
    <span aria-hidden="true" class="{{ $classes }} {{ $accent ? 'accent-'.$accent : '' }} inline-flex items-center justify-center bg-accent-solid font-semibold text-white">{{ $user->initials() }}</span>
@endif
