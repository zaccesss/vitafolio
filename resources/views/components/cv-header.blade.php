@props(['cv'])
{{-- the name and contact line shared by a cv and its cover letter, drawn in the cv's theme --}}
@php
    $user = $cv->user;
    $headline = $cv->displayHeadline();
    $band = $cv->theme === 'modern';
    $headerClass = [
        'classic' => 'border-b border-line',
        'modern' => 'cv-band bg-accent-solid text-white',
        'minimal' => '',
        'plain' => 'border-b-2 border-ink',
    ][$cv->theme];
@endphp
<header class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:p-8 {{ $headerClass }}">
    @unless ($cv->theme === 'plain')<x-avatar :user="$user" size="lg" :accent="$cv->accent" />@endunless
    <div class="min-w-0">
        <h1 id="cv-name" class="text-3xl sm:text-4xl {{ $band ? 'text-white' : '' }}">{{ $user->name }}</h1>
        @if ($headline)<p class="mt-1 text-lg {{ $band ? 'text-white/90' : 'text-muted' }}">{{ $headline }}</p>@endif
        <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm {{ $band ? 'text-white/90' : 'text-muted' }}">
            @if ($user->pronouns)<li>{{ $user->pronouns }}</li>@endif
            @if ($user->location)<li><span class="sr-only">Location: </span>{{ $user->location }}</li>@endif
            @if ($user->university)<li><span class="sr-only">University: </span>{{ $user->university }}</li>@endif
            @if ($cv->key_language)<li>Main language: {{ $cv->key_language }}</li>@endif
            @if ($user->availability !== 'none')<li>Looking for: {{ config('vitafolio.availability')[$user->availability] }}</li>@endif
            @if ($cv->show_email)<li><a class="{{ $band ? 'text-white' : '' }}" href="mailto:{{ $user->email }}">{{ $user->email }}</a></li>@endif
        </ul>
    </div>
</header>
