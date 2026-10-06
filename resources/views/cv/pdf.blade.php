@php
    // the fallback layout, used only when the typst binary is missing. resources/pdf/cv.typ is the
    // real one and the only one that writes tagged pdfs. mpdf reads a small subset of css (no
    // pre-line either, so line breaks become <br>) (tables, no flexbox or grid), so this layout is
    // separate from the web page but follows the same theme, accent, font and section order
    $user = $cv->user;
    // the cover letter reuses this layout, so the letter and the cv print as a matching pair
    $letter ??= false;
    $accent = config('vitafolio.accents')[$cv->accent]['hex'] ?? '#14213d';
    $font = ['sans' => 'dejavusans', 'serif' => 'dejavuserif', 'mono' => 'dejavusansmono'][$cv->font] ?? 'dejavusans';
    $theme = $cv->theme;
    $band = $theme === 'modern';
    $plain = $theme === 'plain';
    $headingColour = $plain ? '#141414' : $accent;
    $details = array_filter([
        $user->pronouns,
        $user->location,
        $user->university,
        $cv->key_language ? 'Main language: '.$cv->key_language : null,
        $user->availability !== 'none' ? 'Looking for: '.(config('vitafolio.availability')[$user->availability] ?? '') : null,
        $cv->show_email ? $user->email : null,
    ]);
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<style>
    body { font-family: {{ $font }}; font-size: 10pt; color: #141414; line-height: 1.45; }
    h1 { font-size: 22pt; margin: 0; color: {{ $band ? '#ffffff' : '#141414' }}; }
    .headline { font-size: 12pt; margin: 2pt 0 0; color: {{ $band ? '#ffffff' : '#55524c' }}; }
    .details { font-size: 9pt; margin: 5pt 0 0; color: {{ $band ? '#ffffff' : '#55524c' }}; }
    .header { width: 100%; {{ $band ? 'background-color: '.$accent.';' : '' }} }
    .header td { vertical-align: middle; padding: {{ $band ? '10pt 12pt' : '0 0 8pt' }}; }
    .rule { border-bottom: {{ $plain ? '1.5pt solid #141414' : '0.75pt solid #d8d4cc' }}; margin-bottom: 10pt; }
    .photo { width: 24mm; height: 24mm; }
    h2 {
        @if ($theme === 'minimal')
            font-size: 13pt; font-weight: normal; font-family: dejavuserif; color: #141414; margin: 14pt 0 5pt;
        @else
            font-size: 9pt; font-weight: bold; letter-spacing: 1.5pt; text-transform: uppercase; color: {{ $headingColour }};
            border-bottom: 1pt solid #d8d4cc; padding-bottom: 3pt; margin: 14pt 0 6pt;
        @endif
    }
    .project { margin: 0 0 7pt; page-break-inside: avoid; }
    .project-title { font-weight: bold; }
    .muted { color: #55524c; font-size: 9pt; }
    .skills { margin: 0; }
    a { color: {{ $plain ? '#141414' : $accent }}; text-decoration: none; }
    .footer { font-size: 8pt; color: #8a8375; text-align: center; }
</style>
</head>
<body>
<htmlpagefooter name="footer"><div class="footer">{{ $user->name }} &middot; {{ $letter ? route('cv.letter', $cv) : route('cv.show', $cv) }} &middot; page {PAGENO} of {nbpg}</div></htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />

<table class="header" cellspacing="0" cellpadding="0">
    <tr>
        @if ($picture && ! $plain)
            <td style="width: 30mm;"><img class="photo" src="{{ $picture }}" alt=""></td>
        @endif
        <td>
            <h1>{{ $user->name }}</h1>
            @if ($cv->displayHeadline())<p class="headline">{{ $cv->displayHeadline() }}</p>@endif
            @if ($details !== [])<p class="details">{{ implode('  ·  ', $details) }}</p>@endif
        </td>
    </tr>
</table>
@unless ($band)<div class="rule"></div>@endunless

@if ($letter)
    <h2>Cover letter</h2>
    @if (filled($cv->letter_to))<p class="muted">{{ $cv->letter_to }}</p>@endif
    <div>{!! nl2br(e($cv->cover_letter)) !!}</div>
@else
@foreach ($cv->orderedSections() as $section)
    @if ($section === 'profile' && filled($cv->profile))
        <h2>Profile</h2>
        <div>{!! nl2br(e($cv->profile)) !!}</div>
    @elseif ($section === 'experience' && filled($cv->experience))
        <h2>Experience</h2>
        <div>{!! nl2br(e($cv->experience)) !!}</div>
    @elseif ($section === 'education' && filled($cv->education))
        <h2>Education</h2>
        <div>{!! nl2br(e($cv->education)) !!}</div>
    @elseif ($section === 'projects' && $cv->projects->isNotEmpty())
        <h2>Projects</h2>
        @foreach ($cv->projects as $project)
            <div class="project">
                <span class="project-title">{{ $project->title }}</span>
                {{-- printed in full, because a link in a printed cv is only useful if it can be typed --}}
                @if ($project->url)<br><a href="{{ $project->url }}" class="muted">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($project->url, '/')) }}</a>@endif
                @if ($project->description)<div>{!! nl2br(e($project->description)) !!}</div>@endif
            </div>
        @endforeach
    @elseif ($section === 'skills' && $cv->tags->isNotEmpty())
        <h2>Skills</h2>
        <p class="skills">{{ $cv->tags->pluck('name')->implode('  ·  ') }}</p>
    @elseif ($section === 'links' && $links !== [])
        <h2>Links</h2>
        <table cellspacing="0" cellpadding="0">
            @foreach ($links as $link)
                <tr>
                    <td style="padding: 0 10pt 2pt 0;"><strong>{{ $link['label'] }}</strong></td>
                    <td style="padding: 0 0 2pt;"><a href="{{ $link['url'] }}">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($link['url'], '/')) }}</a></td>
                </tr>
            @endforeach
        </table>
    @endif
@endforeach
@endif
</body>
</html>
