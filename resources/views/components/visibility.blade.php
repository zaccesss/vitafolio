@props(['value'])
{{-- the visibility word always sits beside its icon, so the meaning never rests on the icon alone --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm font-medium text-muted']) }}>
    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        @switch($value)
            @case('public')
                <rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 7.5-2" />
                @break
            @case('unlisted')
                <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1" /><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1" />
                @break
            @default
                <rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" />
        @endswitch
    </svg>
    {{ __(ucfirst($value)) }}
</span>
