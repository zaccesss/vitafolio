@props(['cv', 'current'])
{{-- only shown when the cv has a letter, so a cv without one looks exactly as before --}}
@if ($cv->hasCoverLetter())
    <nav class="mb-6 flex gap-1 overflow-x-auto border-b-2 border-line no-print" aria-label="{{ __('CV and cover letter') }}">
        <a class="tab-link" href="{{ route('cv.show', $cv) }}" @if ($current === 'cv') aria-current="page" @endif>{{ __('CV') }}</a>
        <a class="tab-link" href="{{ route('cv.letter', $cv) }}" @if ($current === 'letter') aria-current="page" @endif>{{ __('Cover letter') }}</a>
    </nav>
@endif
