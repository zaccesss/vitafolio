<x-layouts.app :title="$user->name" :description="$user->headline ?: __('The profile of :name on :app', ['name' => $user->name, 'app' => config('app.name')])"
    :canonical="route('profile.show', $user->handle)" :noindex="$user->profile_visibility !== 'public' || ! $user->hasVerifiedEmail()" type="profile">
    <x-slot:head>
        {{-- structured data so search engines understand the page; a data block, never executed --}}
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'dateModified' => $user->updated_at?->toIso8601String(),
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $user->name,
                'alternateName' => '@'.$user->handle,
                'description' => $user->headline,
                'image' => $user->hasAvatar() ? $user->avatarUrl() : null,
                'address' => $user->location,
                'alumniOf' => $user->university,
                'sameAs' => array_column($links, 'url'),
                'url' => route('profile.show', $user->handle),
            ]),
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>
    <div class="container-page max-w-4xl py-10">
        @if ($isOwner)
            <div class="alert alert-info mb-6 flex flex-wrap items-center justify-between gap-3">
                <p>{!! __('This is your profile as others see it. It is :visibility.', ['visibility' => '<strong>'.e(__(ucfirst($user->profile_visibility))).'</strong>']) !!}</p>
                <a class="btn btn-sm btn-secondary" href="{{ route('profile.edit') }}">{{ __('Edit profile') }}</a>
            </div>
        @endif

        <header class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
            <x-avatar :user="$user" size="lg" />
            <div>
                <h1 class="text-4xl">{{ $user->name }}</h1>
                <p class="mt-1 text-muted"><span dir="ltr">&#64;{{ $user->handle }}</span>@if ($user->pronouns) · {{ $user->pronouns }}@endif</p>
                @if ($user->headline)<p class="mt-2 text-lg">{{ $user->headline }}</p>@endif
                <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted">
                    @if ($user->location)<li>{{ $user->location }}</li>@endif
                    @if ($user->university)<li>{{ $user->university }}</li>@endif
                    @if ($user->availability !== 'none')<li><span class="badge">{{ __(config('vitafolio.availability')[$user->availability]) }}</span></li>@endif
                </ul>
            </div>
        </header>

        @if ($user->bio)
            <section class="mt-8 max-w-2xl" aria-label="{{ __('About') }}">
                <p class="whitespace-pre-line text-lg" dir="auto">{{ $user->bio }}</p>
            </section>
        @endif

        @if ($links !== [])
            <ul class="mt-6 flex flex-wrap gap-3" aria-label="{{ __('Links') }}">
                @foreach ($links as $link)
                    <li><a class="btn btn-sm btn-secondary" href="{{ $link['url'] }}" rel="noopener noreferrer nofollow ugc me"><x-link-icon :kind="$link['icon']" />{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        @endif

        <section class="mt-12" aria-labelledby="cvs-title">
            <h2 id="cvs-title" class="text-2xl">{{ $isOwner ? __('Your CVs') : __('CVs') }}</h2>
            @if ($cvs->isEmpty())
                <p class="mt-3 text-muted">{{ $isOwner ? __('You have no CVs yet.') : __('No public CVs yet.') }}</p>
            @else
                <ul class="mt-5 grid gap-5 sm:grid-cols-2">
                    @foreach ($cvs as $cv)
                        <li>
                            <article class="card card-hover relative flex h-full flex-col gap-3 p-5 accent-{{ $cv->accent }}">
                                <h3 class="text-lg">
                                    <a href="{{ route('cv.show', $cv) }}" class="text-ink no-underline after:absolute after:inset-0 after:content-[''] hover:underline">{{ $cv->title }}</a>
                                </h3>
                                @if ($cv->displayHeadline())<p class="text-sm text-muted">{{ $cv->displayHeadline() }}</p>@endif
                                @if ($isOwner)<p><x-visibility :value="$cv->visibility" /></p>@endif
                                <p class="mt-auto text-sm text-muted">{{ __('Updated :time', ['time' => $cv->updated_at->diffForHumans()]) }}</p>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>
