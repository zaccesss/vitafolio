<x-layouts.app :title="__('My CVs')" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl">{{ __('My CVs') }}</h1>
                <p class="mt-1 text-muted">{{ trans_choice('{0} Hello :name. You have no CVs.|{1} Hello :name. You have 1 CV.|[2,*] Hello :name. You have :count CVs.', $cvs->count(), ['name' => \Illuminate\Support\Str::before(auth()->user()->name, ' ')]) }}</p>
            </div>
            @if ($canCreate)
                <form method="POST" action="{{ route('cvs.store') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label for="new-title" class="field-label text-sm">{{ __('New CV name') }}</label>
                        <input id="new-title" name="title" class="input min-h-11 py-2" required maxlength="80" placeholder="{{ __('For example: Data analyst CV') }}" @error('title') aria-invalid="true" aria-describedby="new-title-error" @enderror>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Create CV') }}</button>
                    @error('title')<p id="new-title-error" class="field-error w-full">{{ $message }}</p>@enderror
                </form>
            @else
                <p class="badge">{{ __('You have reached the limit of :count CVs', ['count' => config('vitafolio.max_cvs_per_user')]) }}</p>
            @endif
        </div>

        @php($waiting = $cvs->sum('pending_endorsements'))
        @if ($waiting > 0)
            <p class="alert alert-info mt-6">
                {{ trans_choice('{1} 1 endorsement is waiting for your approval.|[2,*] :count endorsements are waiting for your approval.', $waiting) }}
                {{ __('They show on your CVs only once you approve them.') }}
            </p>
        @endif

        <ul class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach ($cvs as $cv)
                <li class="card flex flex-col gap-4 p-5 accent-{{ $cv->accent }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-lg">{{ $cv->title }}</h2>
                            <p class="truncate text-sm text-muted" dir="ltr">{{ preg_replace('#^https?://#', '', route('cv.show', $cv)) }}</p>
                        </div>
                        @if ($cv->hidden_at)
                            <span class="badge shrink-0">{{ __('Hidden by a moderator') }}</span>
                        @else
                            <x-visibility class="shrink-0" :value="$cv->visibility" />
                        @endif
                    </div>
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <div><dt class="text-muted">{{ __('Views') }}</dt><dd class="text-lg font-semibold">{{ number_format($cv->view_count) }}</dd></div>
                        <div><dt class="text-muted">{{ __('Skills') }}</dt><dd class="text-lg font-semibold">{{ $cv->tags->count() }}</dd></div>
                        <div><dt class="text-muted">{{ __('File') }}</dt><dd class="text-lg font-semibold">{{ $cv->document ? __('Yes') : __('No') }}</dd></div>
                    </dl>
                    <p class="text-sm text-muted">{{ __('Updated :time', ['time' => $cv->updated_at->diffForHumans()]) }}</p>
                    @if ($cv->pending_endorsements > 0)
                        <p class="text-sm"><a href="{{ route('cvs.edit', [$cv, 'endorsements']) }}">{{ trans_choice('{1} 1 endorsement to review|[2,*] :count endorsements to review', $cv->pending_endorsements) }}<span class="sr-only"> {{ __('on :title', ['title' => $cv->title]) }}</span></a></p>
                    @endif
                    <div class="mt-auto flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-primary" href="{{ route('cvs.edit', $cv) }}">{{ __('Edit') }}<span class="sr-only"> {{ $cv->title }}</span></a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('cv.show', $cv) }}">{{ __('View') }}<span class="sr-only"> {{ $cv->title }}</span></a>
                        <a class="btn btn-sm btn-secondary" href="{{ route('analytics') }}">{{ __('Analytics') }}<span class="sr-only"> {{ __('for :title', ['title' => $cv->title]) }}</span></a>
                        @if ($cv->visibility !== 'public' && ! $cv->hidden_at)
                            <form method="POST" action="{{ route('cvs.publish', $cv) }}" data-confirm="{{ __('Publish :title? It will be public and listed in Browse CVs. You can make it private again at any time.', ['title' => $cv->title]) }}">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Publish') }}<span class="sr-only"> {{ $cv->title }}</span></button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($cvs->isNotEmpty())
            <section id="stats" class="card mt-10 flex flex-wrap items-center justify-between gap-4 p-6" aria-labelledby="stats-title">
                <div>
                    <h2 id="stats-title" class="text-xl">{{ __('Last 30 days') }}</h2>
                    <p class="mt-1 text-muted">
                        {!! trans_choice('{1} :count view across your CVs.|[0,*] :count views across your CVs.', $recentViews, ['count' => '<strong>'.e(number_format($recentViews)).'</strong>']) !!}
                        {{ __('Each visitor counts once a day and your own visits never count.') }}
                    </p>
                </div>
                <a class="btn btn-secondary" href="{{ route('analytics') }}">{{ __('Open analytics') }}</a>
            </section>
        @endif

        <section class="card mt-6 flex flex-wrap items-center justify-between gap-4 p-6" aria-labelledby="check-title">
            <div>
                <h2 id="check-title" class="text-xl">{{ __('Check a CV') }}</h2>
                <p class="mt-1 text-muted">{{ __('See what an applicant tracking system can read in any CV, with a score and fixes.') }}</p>
            </div>
            <a class="btn btn-secondary" href="{{ route('check') }}">{{ __('Check a CV') }}</a>
        </section>
    </div>
</x-layouts.app>
