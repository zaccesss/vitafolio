<x-layouts.app title="My CVs" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl">My CVs</h1>
                <p class="mt-1 text-muted">Hello {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}. You have {{ $cvs->count() }} {{ \Illuminate\Support\Str::plural('CV', $cvs->count()) }}.</p>
            </div>
            @if ($canCreate)
                <form method="POST" action="{{ route('cvs.store') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label for="new-title" class="field-label text-sm">New CV name</label>
                        <input id="new-title" name="title" class="input min-h-11 py-2" required maxlength="80" placeholder="For example: Data analyst CV" @error('title') aria-invalid="true" aria-describedby="new-title-error" @enderror>
                    </div>
                    <button type="submit" class="btn btn-primary">Create CV</button>
                    @error('title')<p id="new-title-error" class="field-error w-full">{{ $message }}</p>@enderror
                </form>
            @else
                <p class="badge">You have reached the limit of {{ config('vitafolio.max_cvs_per_user') }} CVs</p>
            @endif
        </div>

        @php($waiting = $cvs->sum('pending_endorsements'))
        @if ($waiting > 0)
            <p class="alert alert-info mt-6">
                {{ $waiting }} {{ $waiting === 1 ? 'endorsement is' : 'endorsements are' }} waiting for your approval. They show on your CVs only once you approve them.
            </p>
        @endif

        <ul class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach ($cvs as $cv)
                <li class="card flex flex-col gap-4 p-5 accent-{{ $cv->accent }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-lg">{{ $cv->title }}</h2>
                            <p class="truncate text-sm text-muted">{{ preg_replace('#^https?://#', '', route('cv.show', $cv)) }}</p>
                        </div>
                        <span class="badge shrink-0">
                            @if ($cv->hidden_at) Hidden by a moderator @else {{ ucfirst($cv->visibility) }} @endif
                        </span>
                    </div>
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <div><dt class="text-muted">Views</dt><dd class="text-lg font-semibold">{{ number_format($cv->view_count) }}</dd></div>
                        <div><dt class="text-muted">Skills</dt><dd class="text-lg font-semibold">{{ $cv->tags->count() }}</dd></div>
                        <div><dt class="text-muted">File</dt><dd class="text-lg font-semibold">{{ $cv->document ? 'Yes' : 'No' }}</dd></div>
                    </dl>
                    <p class="text-sm text-muted">Updated {{ $cv->updated_at->diffForHumans() }}</p>
                    @if ($cv->pending_endorsements > 0)
                        <p class="text-sm"><a href="{{ route('cvs.edit', [$cv, 'endorsements']) }}">{{ $cv->pending_endorsements }} {{ $cv->pending_endorsements === 1 ? 'endorsement' : 'endorsements' }} to review<span class="sr-only"> on {{ $cv->title }}</span></a></p>
                    @endif
                    <div class="mt-auto flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-primary" href="{{ route('cvs.edit', $cv) }}">Edit<span class="sr-only"> {{ $cv->title }}</span></a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('cv.show', $cv) }}">View<span class="sr-only"> {{ $cv->title }}</span></a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('analytics') }}">Analytics<span class="sr-only"> for {{ $cv->title }}</span></a>
                        @if ($cv->visibility !== 'public' && ! $cv->hidden_at)
                            <form method="POST" action="{{ route('cvs.publish', $cv) }}" data-confirm="Publish {{ $cv->title }}? It will be public and listed in Browse CVs. You can make it private again at any time.">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-sm btn-secondary">Publish<span class="sr-only"> {{ $cv->title }}</span></button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($cvs->isNotEmpty())
            <section id="stats" class="card mt-10 flex flex-wrap items-center justify-between gap-4 p-6" aria-labelledby="stats-title">
                <div>
                    <h2 id="stats-title" class="text-xl">Last 30 days</h2>
                    <p class="mt-1 text-muted">
                        <strong>{{ number_format($recentViews) }}</strong> {{ $recentViews === 1 ? 'view' : 'views' }} across your CVs.
                        Each visitor counts once a day and your own visits never count.
                    </p>
                </div>
                <a class="btn btn-secondary" href="{{ route('analytics') }}">Open analytics</a>
            </section>
        @endif
    </div>
</x-layouts.app>
