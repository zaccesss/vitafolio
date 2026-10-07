@php
    $user = $cv->user;
    $name = $user->name;
    $headline = $cv->displayHeadline();
    $description = $headline ? $name.': '.$headline : __('The CV of :name on :app', ['name' => $name, 'app' => config('app.name')]);
    $hasBuiltContent = filled($cv->profile) || filled($cv->experience) || filled($cv->education) || $cv->tags->isNotEmpty() || $cv->projects->isNotEmpty();
    $document = $cv->document;
    $headingClass = $cv->theme === 'minimal'
        ? 'font-serif text-2xl text-ink'
        : 'border-b-2 border-line pb-2 text-sm font-bold uppercase tracking-widest '.($cv->theme === 'plain' ? 'text-ink' : 'text-accent');
@endphp
<x-layouts.app :title="__(':name CV', ['name' => $name])" :description="$description" :canonical="route('cv.show', $cv)"
    :noindex="$cv->visibility !== 'public' || $cv->hidden_at || $user->profile_visibility === 'private' || ! $user->hasVerifiedEmail()" :image="route('cv.og', $cv)" type="profile" turnstile>
    <x-slot:head>
        {{-- structured data so search engines understand the page; a data block, never executed --}}
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $name,
                'jobTitle' => $headline,
                'address' => $user->location,
                'alumniOf' => $user->university,
                'knowsAbout' => $cv->tags->pluck('name')->all(),
                'sameAs' => array_column($links, 'url'),
            ]),
            'dateModified' => $cv->updated_at->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="container-page py-8">
        <x-cv-status :cv="$cv" :is-owner="$isOwner" />
        <x-cv-pages :cv="$cv" current="cv" />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            <div class="grid min-w-0 gap-6">
                {{-- the cv is in its own document language whatever language the visitor reads the site in,
                     matching its pdf and word downloads; what the owner wrote reads in its own direction --}}
                <article aria-labelledby="cv-name" lang="{{ \App\Support\Locales::html($cv->documentLocale()) }}" dir="{{ \App\Support\Locales::dir($cv->documentLocale()) }}"
                    class="cv-doc overflow-hidden rounded-card accent-{{ $cv->accent }} font-cv-{{ $cv->font }} {{ $cv->theme === 'minimal' ? '' : 'border border-line bg-surface shadow-card' }}">
                    <x-cv-header :cv="$cv" />

                    <div class="grid gap-8 p-6 sm:p-8">
                        @if (! $hasBuiltContent && $document)
                            <section class="text-center">
                                <h2 class="text-xl">{{ $document->isPdf() ? $cv->label('This CV is a PDF') : $cv->label('This CV is a Word document') }}</h2>
                                <p class="mt-2 text-muted">{{ $document->filename }}, {{ $document->humanSize() }}</p>
                                <a class="btn btn-primary mt-5" href="{{ route('cv.file', $cv) }}">{{ $document->isPdf() ? $cv->label('Open the CV') : $cv->label('Download the CV') }}</a>
                            </section>
                        @elseif (! $hasBuiltContent)
                            <p class="text-muted">{{ $cv->label('This CV has no content yet.') }}</p>
                        @endif

                        @foreach ($cv->orderedSections() as $section)
                            @if ($section === 'profile' && filled($cv->profile))
                                <section aria-labelledby="sec-profile">
                                    <h2 id="sec-profile" class="mb-3 {{ $headingClass }}">{{ $cv->label('Profile') }}</h2>
                                    <div class="prose-cv" dir="auto">{{ $cv->profile }}</div>
                                </section>
                            @elseif ($section === 'experience' && filled($cv->experience))
                                <section aria-labelledby="sec-experience">
                                    <h2 id="sec-experience" class="mb-3 {{ $headingClass }}">{{ $cv->label('Experience') }}</h2>
                                    <div class="prose-cv" dir="auto">{{ $cv->experience }}</div>
                                </section>
                            @elseif ($section === 'education' && filled($cv->education))
                                <section aria-labelledby="sec-education">
                                    <h2 id="sec-education" class="mb-3 {{ $headingClass }}">{{ $cv->label('Education') }}</h2>
                                    <div class="prose-cv" dir="auto">{{ $cv->education }}</div>
                                </section>
                            @elseif ($section === 'projects' && $cv->projects->isNotEmpty())
                                <section aria-labelledby="sec-projects">
                                    <h2 id="sec-projects" class="mb-4 {{ $headingClass }}">{{ $cv->label('Projects') }}</h2>
                                    <ul class="grid gap-5 sm:grid-cols-2">
                                        @foreach ($cv->projects as $project)
                                            <li class="overflow-hidden rounded-xl border border-line bg-surface">
                                                @if ($project->media_type === 'image')
                                                    <img src="{{ \App\Support\Cloudinary::delivery($project->media_url, 'image') }}" alt="{{ $project->media_alt }}" loading="lazy" decoding="async" class="aspect-video w-full object-cover">
                                                @elseif ($project->media_type === 'video')
                                                    {{-- never autoplays; controls let people pause; the description stands in for captions --}}
                                                    <video controls preload="metadata" playsinline class="aspect-video w-full bg-black" aria-label="{{ $project->media_alt }}">
                                                        <source src="{{ \App\Support\Cloudinary::delivery($project->media_url, 'video') }}">
                                                    </video>
                                                @endif
                                                <div class="p-4">
                                                    <h3 class="font-semibold" dir="auto">
                                                        @if ($project->url)<a href="{{ $project->url }}" rel="noopener noreferrer nofollow ugc">{{ $project->title }}</a>@else{{ $project->title }}@endif
                                                    </h3>
                                                    @if ($project->description)<p dir="auto" class="mt-1 text-sm text-muted whitespace-pre-line">{{ $project->description }}</p>@endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </section>
                            @elseif ($section === 'skills' && $cv->tags->isNotEmpty())
                                <section aria-labelledby="sec-skills">
                                    <h2 id="sec-skills" class="mb-3 {{ $headingClass }}">{{ $cv->label('Skills') }}</h2>
                                    <ul class="flex flex-wrap gap-2">
                                        @foreach ($cv->tags as $tag)
                                            <li><a class="tag" href="{{ route('home', ['tags' => [$tag->slug]]) }}">{{ $tag->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </section>
                            @elseif ($section === 'links' && $links !== [])
                                <section aria-labelledby="sec-links">
                                    <h2 id="sec-links" class="mb-3 {{ $headingClass }}">{{ $cv->label('Links') }}</h2>
                                    <ul class="space-y-1.5">
                                        @foreach ($links as $link)
                                            <li class="flex flex-wrap items-center gap-x-2"><x-link-icon :kind="$link['icon']" class="size-4 shrink-0 text-muted" /><a href="{{ $link['url'] }}" rel="noopener noreferrer nofollow ugc">{{ $link['label'] }}</a> <span class="text-sm text-muted" dir="ltr">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($link['url'], '/')) }}</span></li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endif
                        @endforeach

                        <p class="text-sm text-muted">{{ $cv->label('Last updated :date', ['date' => $cv->updated_at->locale($cv->documentLocale())->translatedFormat('j F Y')]) }}</p>
                    </div>
                </article>

                <x-endorsement-list :cv="$cv" :is-owner="$isOwner" :heading-class="$headingClass" />
            </div>

            <aside class="space-y-5 no-print" aria-label="{{ __('Actions') }}">
                <section class="card p-5" aria-labelledby="share-title">
                    <h2 id="share-title" class="text-base">{{ __('Share and download') }}</h2>
                    <div class="mt-4 grid gap-2">
                        <a class="btn btn-primary" href="{{ route('cv.pdf', $cv) }}">{{ __('Download PDF') }}</a>
                        <a class="btn btn-secondary" href="{{ route('cv.word', $cv) }}">{{ __('Download as Word') }}</a>
                        @if ($cv->hasCoverLetter())
                            <a class="btn btn-secondary" href="{{ route('cv.letter', $cv) }}">{{ __('Read the cover letter') }}</a>
                        @endif
                        @if ($document && $hasBuiltContent)
                            <a class="btn btn-secondary" href="{{ route('cv.file', $cv) }}">
                                {{ $document->source === 'latex' ? __('Open LaTeX version') : ($document->isPdf() ? __('Open uploaded PDF') : __('Download Word file')) }}
                            </a>
                        @endif
                        <button type="button" class="btn btn-secondary" data-print hidden>{{ __('Print') }}</button>
                        <div x-data="copyLink" data-url="{{ route('cv.show', $cv) }}" x-cloak>
                            <button type="button" class="btn btn-secondary w-full" @click="copy" x-text="label">{{ __('Copy link') }}</button>
                            <span class="sr-only" aria-live="polite" x-text="announcement"></span>
                        </div>
                    </div>
                    <figure class="mt-5 text-center">
                        <img src="{{ route('cv.qr', $cv) }}" alt="{{ __('QR code linking to this CV') }}" width="160" height="160" class="mx-auto rounded-lg bg-white p-2">
                        <figcaption class="mt-2 text-sm text-muted">{{ __('Scan to open this CV.') }} <a href="{{ route('cv.qr', $cv) }}" download="{{ $cv->slug }}-qr.svg">{{ __('Download QR code') }}</a></figcaption>
                    </figure>
                </section>

                @if ($user->handle && $user->profileVisibleTo(auth()->user()))
                    <section class="card flex items-center gap-4 p-5">
                        <x-avatar :user="$user" size="sm" :accent="$cv->accent" />
                        <p class="text-sm">{!! __('More from :name: :link', ['name' => e($user->firstName()), 'link' => '<a href="'.e(route('profile.show', $user->handle)).'">'.e(__('view profile')).'</a>']) !!}</p>
                    </section>
                @endif

                <x-endorsement-form :cv="$cv" :is-owner="$isOwner" :mine="$myEndorsement" :pending="$pendingEndorsements" />

                @if (! $isOwner && ! $cv->show_email)
                    <a href="#message" class="mobile-cta btn btn-primary no-print lg:hidden">{{ __('Message :name', ['name' => $user->firstName()]) }}</a>
                    <section id="message" class="card p-5" aria-labelledby="message-title">
                        <h2 id="message-title" class="text-base">{{ __('Message :name', ['name' => $user->firstName()]) }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Your message is sent by email. Their address stays private. They can reply to you directly.') }}</p>
                        <x-error-summary bag="message" />
                        <form method="POST" action="{{ route('cv.message', $cv) }}" class="mt-4 grid gap-4">
                            @csrf
                            <x-field name="sender_name" :label="__('Your name')" required autocomplete="name" maxlength="100" error-bag="message" />
                            <x-field name="sender_email" :label="__('Your email')" type="email" required autocomplete="email" maxlength="254" error-bag="message" />
                            <x-field name="message" :label="__('Message')" type="textarea" rows="5" required maxlength="3000" counter error-bag="message" />
                            {{-- hidden from people; only bots fill it in --}}
                            <div class="hidden" aria-hidden="true"><label>{{ __('Website') }} <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            <x-turnstile />
                            <button type="submit" class="btn btn-primary">{{ __('Send message') }}</button>
                        </form>
                    </section>
                @endif

                @unless ($isOwner)
                    <details class="card p-5">
                        <summary class="cursor-pointer font-semibold">{{ __('Report this CV') }}</summary>
                        <form method="POST" action="{{ route('cv.report', $cv) }}" class="mt-4 grid gap-4">
                            @csrf
                            <fieldset class="grid gap-2">
                                <legend class="field-label">{{ __('What is wrong?') }}</legend>
                                @foreach (\App\Models\Report::REASONS as $value => $label)
                                    <label class="flex items-start gap-2 text-sm"><input type="radio" name="reason" value="{{ $value }}" class="radio mt-0.5" required> {{ __($label) }}</label>
                                @endforeach
                            </fieldset>
                            <x-field name="details" :label="__('Details')" type="textarea" rows="3" maxlength="1000" />
                            <x-turnstile />
                            <button type="submit" class="btn btn-secondary">{{ __('Send report') }}</button>
                        </form>
                    </details>
                @endunless
            </aside>
        </div>
    </div>
</x-layouts.app>
