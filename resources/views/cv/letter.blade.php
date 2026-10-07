@php
    $user = $cv->user;
    $headingClass = $cv->theme === 'minimal'
        ? 'font-serif text-2xl text-ink'
        : 'border-b-2 border-line pb-2 text-sm font-bold uppercase tracking-widest '.($cv->theme === 'plain' ? 'text-ink' : 'text-accent');
@endphp
{{-- the letter is kept out of search on the same terms as its cv, so it never shows where the cv would not --}}
<x-layouts.app :title="__(':name cover letter', ['name' => $user->name])" :description="__('A cover letter from :name on :app', ['name' => $user->name, 'app' => config('app.name')])" :canonical="route('cv.letter', $cv)"
    :noindex="$cv->visibility !== 'public' || $cv->hidden_at || $user->profile_visibility === 'private' || ! $user->hasVerifiedEmail()" :image="route('cv.og', $cv)">
    <div class="container-page py-8">
        <x-cv-status :cv="$cv" :is-owner="$isOwner" letter />
        <x-cv-pages :cv="$cv" current="letter" />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            {{-- the letter follows its cv's document language, the same as its downloads --}}
            <article aria-labelledby="cv-name" lang="{{ \App\Support\Locales::html($cv->documentLocale()) }}" dir="{{ \App\Support\Locales::dir($cv->documentLocale()) }}"
                class="cv-doc overflow-hidden rounded-card accent-{{ $cv->accent }} font-cv-{{ $cv->font }} {{ $cv->theme === 'minimal' ? '' : 'border border-line bg-surface shadow-card' }}">
                <x-cv-header :cv="$cv" />

                <section class="p-6 sm:p-8" aria-labelledby="sec-letter">
                    <h2 id="sec-letter" class="mb-3 {{ $headingClass }}">{{ $cv->label('Cover letter') }}</h2>
                    @if (filled($cv->letter_to))<p dir="auto" class="mb-5 text-muted">{{ $cv->letter_to }}</p>@endif
                    <div class="prose-cv max-w-prose" dir="auto">{{ $cv->cover_letter }}</div>
                    <p class="mt-8 text-sm text-muted">{{ $cv->label('Last updated :date', ['date' => $cv->updated_at->locale($cv->documentLocale())->translatedFormat('j F Y')]) }}</p>
                </section>
            </article>

            <aside class="space-y-5 no-print" aria-label="{{ __('Actions') }}">
                <section class="card p-5" aria-labelledby="letter-share-title">
                    <h2 id="letter-share-title" class="text-base">{{ __('Download') }}</h2>
                    <div class="mt-4 grid gap-2">
                        <a class="btn btn-primary" href="{{ route('cv.letter.pdf', $cv) }}">{{ __('Download letter as PDF') }}</a>
                        <a class="btn btn-secondary" href="{{ route('cv.letter.word', $cv) }}">{{ __('Download letter as Word') }}</a>
                        <a class="btn btn-secondary" href="{{ route('cv.show', $cv) }}">{{ __('Back to the CV') }}</a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
