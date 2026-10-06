@props(['provider'])
{{-- each provider's official mark, as its sign-in brand guidance asks: google's four-colour g, the github
     mark in the text colour so it suits both themes, microsoft's four squares and linkedin's blue in.
     decorative only, since the button text already names the provider --}}
@switch($provider)
    @case('google')
        <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true" focusable="false" class="shrink-0">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        @break
    @case('github')
        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="shrink-0" fill="currentColor">
            <path d="M12 .3a12 12 0 0 0-3.8 23.38c.6.12.83-.26.83-.57L9 21.07c-3.34.72-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.08-.74.09-.73.09-.73 1.2.09 1.83 1.24 1.83 1.24 1.07 1.83 2.81 1.3 3.5 1 .1-.78.42-1.31.76-1.61-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.14-.3-.54-1.52.1-3.18 0 0 1-.32 3.3 1.23a11.5 11.5 0 0 1 6 0c2.28-1.55 3.29-1.23 3.29-1.23.64 1.66.24 2.88.12 3.18a4.65 4.65 0 0 1 1.23 3.22c0 4.61-2.8 5.63-5.48 5.92.42.36.81 1.1.81 2.22l-.01 3.29c0 .31.2.69.82.57A12 12 0 0 0 12 .3"/>
        </svg>
        @break
    @case('microsoft')
        <svg width="20" height="20" viewBox="0 0 23 23" aria-hidden="true" focusable="false" class="shrink-0">
            <path fill="#F25022" d="M1 1h10v10H1z"/>
            <path fill="#7FBA00" d="M12 1h10v10H12z"/>
            <path fill="#00A4EF" d="M1 12h10v10H1z"/>
            <path fill="#FFB900" d="M12 12h10v10H12z"/>
        </svg>
        @break
    @case('linkedin')
        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="shrink-0">
            <rect width="24" height="24" rx="3" fill="#0A66C2"/>
            <path fill="#FFFFFF" d="M7.1 9.4H4.6V19h2.5V9.4zM5.85 5.2a1.45 1.45 0 1 0 0 2.9 1.45 1.45 0 0 0 0-2.9zM19.4 13.7c0-2.6-1.4-4.5-3.9-4.5-1.3 0-2.2.7-2.6 1.4V9.4h-2.4V19h2.5v-5c0-1.3.3-2.5 1.9-2.5 1.6 0 1.6 1.5 1.6 2.6V19h2.5v-5.3z"/>
        </svg>
        @break
@endswitch
