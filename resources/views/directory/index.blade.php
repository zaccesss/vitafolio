@php
    $filtering = $q !== '' || $selectedTags !== [] || $university !== '' || ($availability !== '' && $availability !== 'none');
    $toggleTag = function (string $slug) use ($selectedTags) {
        $next = in_array($slug, $selectedTags, true) ? array_values(array_diff($selectedTags, [$slug])) : [...$selectedTags, $slug];
        return route('home', array_filter(array_merge(request()->except(['tags', 'page']), ['tags' => $next])));
    };
@endphp
<x-layouts.app :canonical="route('home')">
    <x-slot:head>
        {{-- tells search engines the site's name, logo and that the directory can be searched; a data block, never executed --}}
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'WebSite', 'name' => config('app.name'), 'url' => route('home'),
                    'potentialAction' => ['@type' => 'SearchAction', 'target' => route('home').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
                array_filter(['@type' => 'Organization', 'name' => config('app.name'), 'url' => route('home'), 'logo' => asset('icon-512.png'),
                    'sameAs' => array_values(array_filter([config('vitafolio.linkedin_url'), config('vitafolio.source_url')])) ?: null]),
            ],
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-slot:head>
    @unless ($filtering || $cvs->currentPage() > 1)
        <section class="container-page grid items-center gap-10 py-14 lg:grid-cols-2 lg:py-20" aria-labelledby="hero-title">
            <div>
                <p class="badge mb-5">Free for students, graduates and everyone in between</p>
                <h1 id="hero-title" class="text-4xl leading-tight sm:text-5xl">Every version of your CV, in one place.</h1>
                <p class="mt-5 max-w-xl text-lg text-muted">
                    Keep a CV for every kind of role, upload the ones you already have and choose exactly who sees each one.
                    Share it with a single link, a QR code or a polished PDF.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a class="btn btn-primary" href="{{ route('dashboard') }}">Go to my CVs</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('register') }}">Create your CV</a>
                    @endauth
                    <a class="btn btn-secondary" href="#browse">Browse CVs</a>
                </div>
                <ul class="mt-8 grid gap-2 text-sm text-muted sm:grid-cols-2">
                    <li class="flex gap-2"><span aria-hidden="true" class="text-ok">&#10003;</span> Public, unlisted or private, per CV</li>
                    <li class="flex gap-2"><span aria-hidden="true" class="text-ok">&#10003;</span> Upload a PDF or Word file</li>
                    <li class="flex gap-2"><span aria-hidden="true" class="text-ok">&#10003;</span> Four themes and seven accents</li>
                    <li class="flex gap-2"><span aria-hidden="true" class="text-ok">&#10003;</span> Messages without sharing your email</li>
                </ul>
            </div>
            {{-- decorative: three stacked cv sheets drawn with markup, so they stay sharp and follow the theme --}}
            <div class="hero-stack" aria-hidden="true">
                <div class="hero-sheet top-0 rotate-[-6deg] opacity-70 accent-teal">
                    <span class="hero-line w-1/2 bg-accent-soft"></span>
                </div>
                <div class="hero-sheet top-6 rotate-[4deg] opacity-85 accent-blue">
                    <span class="hero-line w-2/3 bg-accent-soft"></span>
                </div>
                <div class="hero-sheet top-12 accent-purple">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-12 items-center justify-center rounded-full bg-accent-solid text-lg font-bold text-white">JS</span>
                        <div class="flex-1 space-y-2">
                            <span class="block text-base font-semibold text-ink">Jordan Smith</span>
                            <span class="hero-line w-3/4"></span>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2.5">
                        <span class="hero-line w-full"></span>
                        <span class="hero-line w-11/12"></span>
                        <span class="hero-line w-4/5"></span>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-1.5">
                        <span class="tag">Python</span><span class="tag">Embedded C</span><span class="tag">PCB design</span>
                    </div>
                </div>
            </div>
        </section>
    @endunless

    <section id="browse" class="container-page py-8" aria-labelledby="browse-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="browse-title" class="text-2xl">Browse CVs</h2>
                <p class="mt-1 text-muted">Search public CVs and people by name, @handle, role, skill or university.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('home') }}#browse" role="search" class="card mt-6 grid gap-4 p-5 md:grid-cols-12 md:items-end">
            @foreach ($selectedTags as $tag)<input type="hidden" name="tags[]" value="{{ $tag }}">@endforeach
            <div class="md:col-span-4">
                <label for="q" class="field-label">Search</label>
                <input id="q" name="q" type="search" value="{{ $q }}" class="input" placeholder="For example: Python, data analyst or @handle" autocomplete="off">
            </div>
            <div class="md:col-span-3">
                <label for="university" class="field-label">University</label>
                <input id="university" name="university" type="text" value="{{ $university }}" class="input" list="university-list" placeholder="Any university" autocomplete="off">
                <datalist id="university-list">
                    @foreach ($universities as $name)<option value="{{ $name }}">@endforeach
                </datalist>
            </div>
            <div class="md:col-span-2">
                <label for="availability" class="field-label">Looking for</label>
                <select id="availability" name="availability" class="input">
                    <option value="">Anything</option>
                    @foreach (config('vitafolio.availability') as $value => $label)
                        @continue($value === 'none')
                        <option value="{{ $value }}" @selected($availability === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="sort" class="field-label">Sort by</label>
                <select id="sort" name="sort" class="input">
                    <option value="updated" @selected($sort === 'updated')>Recently updated</option>
                    <option value="name" @selected($sort === 'name')>Name</option>
                    <option value="views" @selected($sort === 'views')>Most viewed</option>
                </select>
            </div>
            <div class="md:col-span-1">
                <button type="submit" class="btn btn-primary w-full">Search</button>
            </div>
        </form>

        @if ($popularTags->isNotEmpty())
            <nav class="mt-5" aria-labelledby="tags-title">
                <h3 id="tags-title" class="text-sm font-semibold text-muted">Filter by skill <span class="font-normal">(select several to narrow down)</span></h3>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($popularTags as $tag)
                        @php($active = in_array($tag->slug, $selectedTags, true))
                        <li>
                            <a href="{{ $toggleTag($tag->slug) }}#browse" class="tag {{ $active ? 'tag-active' : '' }}" @if($active) aria-current="true" @endif>
                                @if ($active)<span aria-hidden="true">&#10003;&nbsp;</span>@endif{{ $tag->name }}
                                <span class="ml-1 opacity-80">{{ $tag->cvs_count }}</span>
                                @if ($active)<span class="sr-only"> (selected, select to remove)</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($people->isNotEmpty())
            <section class="mt-8" aria-labelledby="people-title">
                <h3 id="people-title" class="text-lg">People</h3>
                <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($people as $person)
                        <li>
                            <a href="{{ route('profile.show', $person->handle) }}" class="card card-hover flex items-center gap-3 p-4 no-underline">
                                <x-avatar :user="$person" size="sm" />
                                <span class="min-w-0">
                                    <span class="block font-semibold text-ink">{{ $person->name }}</span>
                                    <span class="block truncate text-sm text-muted">{{ '@'.$person->handle }}@if ($person->headline) &middot; {{ $person->headline }}@endif</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <p class="font-semibold" role="status">
                {{ $cvs->total() }} {{ \Illuminate\Support\Str::plural('CV', $cvs->total()) }}{{ $filtering ? ' match your search' : '' }}
            </p>
            @if ($filtering)<a href="{{ route('home') }}#browse" class="text-sm">Clear all filters</a>@endif
        </div>

        @if ($cvs->isEmpty())
            <div class="card mt-4 py-14 text-center">
                <h3 class="text-xl">No CVs found</h3>
                <p class="mx-auto mt-2 max-w-md text-muted">
                    @if ($filtering) Try fewer filters or a different search. @else Be the first to publish a CV. @endif
                </p>
                @guest<a href="{{ route('register') }}" class="btn btn-primary mt-6">Create your CV</a>@endguest
            </div>
        @else
            <ul class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cvs as $cv)
                    <li><x-cv-card :cv="$cv" heading-level="h3" /></li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $cvs->fragment('browse')->links() }}</div>
        @endif
    </section>
</x-layouts.app>
