@props(['size' => 32])
{{-- a cv page with a folded corner and a v that doubles as a tick --}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
    <rect width="64" height="64" rx="16" fill="#14213D"/>
    <path d="M14 16h22l12 12v22a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V20a4 4 0 0 1 4-4z" fill="#FFFFFF"/>
    <path d="M36 16v12h12" fill="#B08D57"/>
    <path d="M20 34l8 12 8-12" fill="none" stroke="#14213D" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
