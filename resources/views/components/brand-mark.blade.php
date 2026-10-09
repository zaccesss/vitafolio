@props(['size' => 32])
{{-- a cv page with a folded corner and a v that doubles as a tick. The page is white with a navy outline and no
     tile, so it reads on the light and the dark theme alike; app icons keep the navy tile in resources/brand --}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="{{ $size }}" height="{{ $size }}" viewBox="6 10 50 50" aria-hidden="true" focusable="false">
    <path d="M18 16h18l12 12v22a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V20a4 4 0 0 1 4-4z" fill="#FFFFFF" stroke="#14213D" stroke-width="2.5" stroke-linejoin="round"/>
    <path d="M36 16v12h12z" fill="#B08D57" stroke="#14213D" stroke-width="2.5" stroke-linejoin="round"/>
    <path d="M22 34l8 12 8-12" fill="none" stroke="#14213D" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
