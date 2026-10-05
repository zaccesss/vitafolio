@php
    $user = $cv->user;
    $name = $user->name;
    $headline = $cv->displayHeadline();
    $description = $headline ? $name.': '.$headline : 'The CV of '.$name.' on '.config('app.name');
    $hasBuiltContent = filled($cv->profile) || filled($cv->experience) || filled($cv->education) || $cv->tags->isNotEmpty() || $cv->projects->isNotEmpty();
    $document = $cv->document;
    $headingClass = $cv->theme === 'minimal'
        ? 'font-serif text-2xl text-ink'
        : 'border-b-2 border-line pb-2 text-sm font-bold uppercase tracking-widest '.($cv->theme === 'plain' ? 'text-ink' : 'text-accent');
    $band = $cv->theme === 'modern';
    $headerClass = [
        'classic' => 'border-b border-line',
        'modern' => 'cv-band bg-accent-solid text-white',
        'minimal' => '',
        'plain' => 'border-b-2 border-ink',
    ][$cv->theme];
@endphp
<x-layouts.app :title="$name.' CV'" :description="$description" :canonical="route('cv.show', $cv)"
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
        @if ($isOwner || auth()->user()?->isAdmin())
            <div class="alert alert-info mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
                <p>
                    @if ($cv->hidden_at)
                        <strong>Hidden by a moderator.</strong> Only you and admins can see this CV.
                    @elseif ($cv->visibility === 'private')
                        <strong>Private.</strong> Only you can see this CV.
                    @elseif ($cv->visibility === 'unlisted')
                        <strong>Unlisted.</strong> Anyone with the link can see it, but it is not in the directory.
                    @else
                        <strong>Public.</strong> Listed in the directory.
                    @endif
                </p>
                @if ($isOwner)<a class="btn btn-sm btn-secondary" href="{{ route('cvs.edit', $cv) }}">Edit this CV</a>@endif
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            <article aria-labelledby="cv-name"
                class="cv-doc overflow-hidden rounded-card accent-{{ $cv->accent }} font-cv-{{ $cv->font }} {{ $cv->theme === 'minimal' ? '' : 'border border-line bg-surface shadow-card' }}">
                <header class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:p-8 {{ $headerClass }}">
                    @unless ($cv->theme === 'plain')<x-avatar :user="$user" size="lg" :accent="$cv->accent" />@endunless
                    <div class="min-w-0">
                        <h1 id="cv-name" class="text-3xl sm:text-4xl {{ $band ? 'text-white' : '' }}">{{ $name }}</h1>
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

                <div class="grid gap-8 p-6 sm:p-8">
                    @if (! $hasBuiltContent && $document)
                        <section class="text-center">
                            <h2 class="text-xl">This CV is a {{ $document->isPdf() ? 'PDF' : 'Word document' }}</h2>
                            <p class="mt-2 text-muted">{{ $document->filename }}, {{ $document->humanSize() }}</p>
                            <a class="btn btn-primary mt-5" href="{{ route('cv.file', $cv) }}">{{ $document->isPdf() ? 'Open the CV' : 'Download the CV' }}</a>
                        </section>
                    @elseif (! $hasBuiltContent)
                        <p class="text-muted">This CV has no content yet.</p>
                    @endif

                    @foreach ($cv->orderedSections() as $section)
                        @if ($section === 'profile' && filled($cv->profile))
                            <section aria-labelledby="sec-profile">
                                <h2 id="sec-profile" class="mb-3 {{ $headingClass }}">Profile</h2>
                                <div class="prose-cv">{{ $cv->profile }}</div>
                            </section>
                        @elseif ($section === 'experience' && filled($cv->experience))
                            <section aria-labelledby="sec-experience">
                                <h2 id="sec-experience" class="mb-3 {{ $headingClass }}">Experience</h2>
                                <div class="prose-cv">{{ $cv->experience }}</div>
                            </section>
                        @elseif ($section === 'education' && filled($cv->education))
                            <section aria-labelledby="sec-education">
                                <h2 id="sec-education" class="mb-3 {{ $headingClass }}">Education</h2>
                                <div class="prose-cv">{{ $cv->education }}</div>
                            </section>
                        @elseif ($section === 'projects' && $cv->projects->isNotEmpty())
                            <section aria-labelledby="sec-projects">
                                <h2 id="sec-projects" class="mb-4 {{ $headingClass }}">Projects</h2>
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
                                                <h3 class="font-semibold">
                                                    @if ($project->url)<a href="{{ $project->url }}" rel="noopener noreferrer nofollow ugc">{{ $project->title }}</a>@else{{ $project->title }}@endif
                                                </h3>
                                                @if ($project->description)<p class="mt-1 text-sm text-muted whitespace-pre-line">{{ $project->description }}</p>@endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @elseif ($section === 'skills' && $cv->tags->isNotEmpty())
                            <section aria-labelledby="sec-skills">
                                <h2 id="sec-skills" class="mb-3 {{ $headingClass }}">Skills</h2>
                                <ul class="flex flex-wrap gap-2">
                                    @foreach ($cv->tags as $tag)
                                        <li><a class="tag" href="{{ route('home', ['tags' => [$tag->slug]]) }}">{{ $tag->name }}</a></li>
                                    @endforeach
                                </ul>
                            </section>
                        @elseif ($section === 'links' && $links !== [])
                            <section aria-labelledby="sec-links">
                                <h2 id="sec-links" class="mb-3 {{ $headingClass }}">Links</h2>
                                <ul class="space-y-1.5">
                                    @foreach ($links as $link)
                                        <li class="flex flex-wrap items-center gap-x-2"><x-link-icon :kind="$link['icon']" class="size-4 shrink-0 text-muted" /><a href="{{ $link['url'] }}" rel="noopener noreferrer nofollow ugc">{{ $link['label'] }}</a> <span class="text-sm text-muted">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($link['url'], '/')) }}</span></li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    @endforeach

                    <p class="text-sm text-muted">Last updated {{ $cv->updated_at->format('j F Y') }}</p>
                </div>
            </article>

            <aside class="space-y-5 no-print" aria-label="Actions">
                <section class="card p-5" aria-labelledby="share-title">
                    <h2 id="share-title" class="text-base">Share and download</h2>
                    <div class="mt-4 grid gap-2">
                        <a class="btn btn-primary" href="{{ route('cv.pdf', $cv) }}">Download PDF</a>
                        @if ($document && $hasBuiltContent)
                            <a class="btn btn-secondary" href="{{ route('cv.file', $cv) }}">
                                {{ $document->source === 'latex' ? 'Open LaTeX version' : ($document->isPdf() ? 'Open uploaded PDF' : 'Download Word file') }}
                            </a>
                        @endif
                        <button type="button" class="btn btn-secondary" data-print hidden>Print</button>
                        <div x-data="copyLink" data-url="{{ route('cv.show', $cv) }}" x-cloak>
                            <button type="button" class="btn btn-secondary w-full" @click="copy" x-text="label">Copy link</button>
                            <span class="sr-only" aria-live="polite" x-text="announcement"></span>
                        </div>
                    </div>
                    <figure class="mt-5 text-center">
                        <img src="{{ route('cv.qr', $cv) }}" alt="QR code linking to this CV" width="160" height="160" class="mx-auto rounded-lg bg-white p-2">
                        <figcaption class="mt-2 text-sm text-muted">Scan to open this CV. <a href="{{ route('cv.qr', $cv) }}" download="{{ $cv->slug }}-qr.svg">Download QR code</a></figcaption>
                    </figure>
                </section>

                @if ($user->handle && $user->profileVisibleTo(auth()->user()))
                    <section class="card flex items-center gap-4 p-5">
                        <x-avatar :user="$user" size="sm" :accent="$cv->accent" />
                        <p class="text-sm">More from {{ $user->firstName() }}: <a href="{{ route('profile.show', $user->handle) }}">view profile</a></p>
                    </section>
                @endif

                @if (! $isOwner && ! $cv->show_email)
                    <section id="message" class="card p-5" aria-labelledby="message-title">
                        <h2 id="message-title" class="text-base">Message {{ $user->firstName() }}</h2>
                        <p class="mt-1 text-sm text-muted">Your message is sent by email. Their address stays private. They can reply to you directly.</p>
                        <x-error-summary bag="message" />
                        <form method="POST" action="{{ route('cv.message', $cv) }}" class="mt-4 grid gap-4">
                            @csrf
                            <x-field name="sender_name" label="Your name" required autocomplete="name" maxlength="100" error-bag="message" />
                            <x-field name="sender_email" label="Your email" type="email" required autocomplete="email" maxlength="254" error-bag="message" />
                            <x-field name="message" label="Message" type="textarea" rows="5" required maxlength="3000" counter error-bag="message" />
                            {{-- hidden from people; only bots fill it in --}}
                            <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            <x-turnstile />
                            <button type="submit" class="btn btn-primary">Send message</button>
                        </form>
                    </section>
                @endif

                @unless ($isOwner)
                    <details class="card p-5">
                        <summary class="cursor-pointer font-semibold">Report this CV</summary>
                        <form method="POST" action="{{ route('cv.report', $cv) }}" class="mt-4 grid gap-4">
                            @csrf
                            <fieldset class="grid gap-2">
                                <legend class="field-label">What is wrong?</legend>
                                @foreach (\App\Models\Report::REASONS as $value => $label)
                                    <label class="flex items-start gap-2 text-sm"><input type="radio" name="reason" value="{{ $value }}" class="radio mt-0.5" required> {{ $label }}</label>
                                @endforeach
                            </fieldset>
                            <x-field name="details" label="Details" type="textarea" rows="3" maxlength="1000" />
                            <x-turnstile />
                            <button type="submit" class="btn btn-secondary">Send report</button>
                        </form>
                    </details>
                @endunless
            </aside>
        </div>
    </div>
</x-layouts.app>
