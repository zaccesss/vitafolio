@props(['cv', 'headingLevel' => 'h2'])
<article class="card card-hover reveal relative flex h-full flex-col gap-4 p-5 accent-{{ $cv->accent }}">
    <div class="flex items-center gap-4">
        <x-avatar :user="$cv->user" :accent="$cv->accent" />
        <div class="min-w-0">
            <{{ $headingLevel }} class="text-lg leading-snug">
                {{-- the link covers the whole card; the tags below sit above it and stay clickable --}}
                <a href="{{ route('cv.show', $cv) }}" class="text-ink no-underline after:absolute after:inset-0 after:rounded-card after:content-[''] hover:underline">{{ $cv->user->name }}</a>
            </{{ $headingLevel }}>
            @if ($cv->displayHeadline())<p class="truncate text-sm text-muted">{{ $cv->displayHeadline() }}</p>@endif
        </div>
    </div>
    @if ($cv->tags->isNotEmpty())
        <ul class="relative z-10 flex flex-wrap gap-1.5" aria-label="Skills">
            @foreach ($cv->tags->take(6) as $tag)
                <li><a class="tag" href="{{ route('home', ['tags' => [$tag->slug]]) }}">{{ $tag->name }}</a></li>
            @endforeach
            @if ($cv->tags->count() > 6)<li class="tag border-dashed">+{{ $cv->tags->count() - 6 }} more</li>@endif
        </ul>
    @endif
    <p class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
        @if ($cv->user->university)<span>{{ $cv->user->university }}</span>@endif
        @if ($cv->user->availability !== 'none')<span class="badge">{{ config('vitafolio.availability')[$cv->user->availability] }}</span>@endif
        <span>Updated {{ $cv->updated_at->diffForHumans() }}</span>
    </p>
</article>
