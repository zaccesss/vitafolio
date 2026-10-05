@props(['kind' => 'website'])
{{-- a kind of site rather than a brand logo: always shown beside its text label, so it is decorative --}}
<svg {{ $attributes->merge(['class' => 'size-4 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($kind)
        @case('code')
            <path d="M8 7l-5 5 5 5M16 7l5 5-5 5M14 4l-4 16" />
            @break
        @case('work')
            <rect x="3" y="7" width="18" height="13" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18" />
            @break
        @case('research')
            <path d="M2 9l10-5 10 5-10 5z" /><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5M22 9v6" />
            @break
        @case('chat')
            <path d="M4 5h16v11H9l-5 4z" /><path d="M8 9h8M8 12h5" />
            @break
        @case('trophy')
            <path d="M8 4h8v5a4 4 0 0 1-8 0zM8 6H4v1a4 4 0 0 0 4 4M16 6h4v1a4 4 0 0 1-4 4M12 13v4M8 20h8M10 17h4" />
            @break
        @case('package')
            <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z" /><path d="M4 7.5l8 4.5 8-4.5M12 12v9" />
            @break
        @case('image')
            <rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="M21 16l-5-5-9 9" />
            @break
        @case('writing')
            <path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z" /><path d="M13.5 6.5l4 4" />
            @break
        @case('video')
            <rect x="2" y="5" width="20" height="14" rx="3" /><path d="M10 9l5 3-5 3z" />
            @break
        @case('social')
            <circle cx="12" cy="12" r="4" /><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0V12a9 9 0 1 0-3.5 7.1" />
            @break
        @default
            <circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z" />
    @endswitch
</svg>
