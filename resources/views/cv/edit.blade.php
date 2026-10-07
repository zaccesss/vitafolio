@php
    // the new-tab marker inside translated sentences, matching the x-new-tab component
    $newTab = '<span aria-hidden="true" class="ms-0.5 inline-block rtl:-scale-x-100">&#8599;</span><span class="sr-only"> '.e(__('(opens in a new tab)')).'</span>';
    $tabs = ['details' => __('Content'), 'letter' => __('Cover letter'), 'endorsements' => __('Endorsements'), 'projects' => __('Projects'), 'file' => __('File and LaTeX'), 'settings' => __('Look and privacy'), 'import' => __('Import and export')];
    $sectionLabels = ['profile' => __('Profile'), 'experience' => __('Experience'), 'projects' => __('Projects'), 'education' => __('Education'), 'skills' => __('Skills'), 'links' => __('Links')];
    $cloudinary = \App\Support\Cloudinary::enabled();
    $limits = config('vitafolio.limits');
    $mediaHint = __('Images up to :image MB. Videos up to :video MB and :seconds seconds.', ['image' => intdiv($limits['image_kb'], 1024), 'video' => intdiv($limits['video_kb'], 1024), 'seconds' => $limits['video_seconds']]);
@endphp
<x-layouts.app :title="__('Edit :title', ['title' => $cv->title])" noindex :turnstile="$tab === 'endorsements'">
    <div class="container-page py-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-muted">
            <ol class="flex flex-wrap gap-2">
                <li><a href="{{ route('dashboard') }}">{{ __('My CVs') }}</a> <span aria-hidden="true" class="inline-block rtl:-scale-x-100">/</span></li>
                <li aria-current="page">{{ $cv->title }}</li>
            </ol>
        </nav>

        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl">{{ $cv->title }}</h1>
                <p class="mt-1 text-muted">
                    <x-visibility class="align-middle" :value="$cv->visibility" />
                    <span class="ms-2" dir="ltr">{{ preg_replace('#^https?://#', '', route('cv.show', $cv)) }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a class="btn btn-secondary" href="{{ route('help.topic', 'building-a-cv') }}" target="_blank" rel="noopener">{{ __('Help') }}<x-new-tab /></a>
                <a class="btn btn-secondary" href="{{ route('cv.show', $cv) }}" target="_blank" rel="noopener">{{ __('View this CV') }}<x-new-tab /></a>
            </div>
        </div>

        <nav class="mt-8 flex gap-1 overflow-x-auto border-b-2 border-line" aria-label="{{ __('Edit sections') }}">
            @foreach ($tabs as $key => $label)
                <a class="tab-link" href="{{ route('cvs.edit', [$cv, $key]) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}@if ($key === 'endorsements' && $cv->pending_endorsements > 0) <span class="badge ms-1">{{ $cv->pending_endorsements }}<span class="sr-only"> {{ __('waiting for approval') }}</span></span>@endif</a>
            @endforeach
        </nav>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="min-w-0">
                <x-error-summary />

                @if ($tab === 'details')
                    <p class="alert alert-info mb-6">{!! __('Your name, photo, location, university and links come from :profile and are shared by all your CVs.', ['profile' => '<a href="'.e(route('profile.edit')).'" target="_blank" rel="noopener">'.e(__('your profile')).$newTab.'</a>']) !!}</p>
                    <form method="POST" action="{{ route('cvs.details', $cv) }}" class="card grid gap-5 p-6 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <x-field name="headline" :label="__('Headline for this CV')" :value="$cv->headline" maxlength="120"
                                 :hint="$user->headline ? __('Leave empty to use your profile headline: :headline.', ['headline' => $user->headline]) : __('Leave empty to use your profile headline.')" dir="auto" />
                        <x-field name="key_language" :label="__('Main language or tool')" :value="$cv->key_language" maxlength="60" :hint="__('For example: Python')" />
                        <x-field class="sm:col-span-2" name="profile" :label="__('Profile')" type="textarea" :value="$cv->profile" rows="5" maxlength="3000" counter
                                 :hint="__('A short summary aimed at the roles this CV is for.')" />
                        <x-field class="sm:col-span-2" name="skills" :label="__('Skills')" :value="\App\Support\Tags::toText($cv)" maxlength="1500"
                                 :hint="__('Separate skills with commas, for example: Python, SQL, Figma. Each becomes a searchable tag.')" />
                        <x-field class="sm:col-span-2" name="experience" :label="__('Experience')" type="textarea" :value="$cv->experience" rows="8" maxlength="5000" counter
                                 :hint="__('Roles and what you achieved. Start a line with a dash and a space for a bullet point.')" />
                        <x-field class="sm:col-span-2" name="education" :label="__('Education')" type="textarea" :value="$cv->education" rows="5" maxlength="5000" counter />
                        <div class="flex flex-wrap gap-3 sm:col-span-2">
                            <button type="submit" class="btn btn-primary">{{ __('Save content') }}</button>
                            <a class="btn btn-secondary" href="{{ route('cv.show', $cv) }}" target="_blank" rel="noopener">{{ __('Preview') }}<x-new-tab /></a>
                        </div>
                    </form>

                    <section class="card mt-8 p-6" aria-labelledby="order-title">
                        <h2 id="order-title" class="text-xl">{{ __('Section order') }}</h2>
                        <p class="mt-1 text-muted">{{ __('Drag the sections or use the buttons to choose the order they appear on this CV and its PDF.') }}</p>
                        <div class="mt-4" data-vue="SectionOrder" data-props="{{ json_encode([
                            'order' => $cv->orderedSections(),
                            'labels' => $sectionLabels,
                            'saveUrl' => route('cvs.order', $cv),
                            'csrf' => csrf_token(),
                        ]) }}">
                            {{-- without javascript the current order is shown as a list --}}
                            <ol class="list-decimal ps-6">@foreach ($cv->orderedSections() as $key)<li>{{ $sectionLabels[$key] }}</li>@endforeach</ol>
                        </div>
                    </section>

                @elseif ($tab === 'letter')
                    <section class="card p-6" aria-labelledby="letter-title">
                        <h2 id="letter-title" class="text-xl">{{ __('Cover letter') }}</h2>
                        <p class="mt-1 text-muted">{{ __('An optional letter to send with this CV. It uses the same theme and privacy setting as the CV. It opens from the CV\'s own page. Leave it empty and nobody sees a letter at all.') }}</p>
                        <form method="POST" action="{{ route('cvs.letter', $cv) }}" class="mt-5 grid gap-5">
                            @csrf @method('PUT')
                            <x-field name="letter_to" :label="__('Who it is for')" :value="$cv->letter_to" maxlength="160"
                                     :hint="__('Shown above the letter, for example: Hiring team, Graduate software engineer at Acme.')" />
                            <x-field name="cover_letter" :label="__('Letter')" type="textarea" :value="$cv->cover_letter" rows="14" maxlength="6000" counter
                                     :hint="__('Plain paragraphs. Leave a blank line between paragraphs.')" />
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="btn btn-primary">{{ __('Save cover letter') }}</button>
                                @if ($cv->hasCoverLetter())
                                    <a class="btn btn-secondary" href="{{ route('cv.letter', $cv) }}" target="_blank" rel="noopener">{{ __('Preview') }}<x-new-tab /></a>
                                @endif
                            </div>
                        </form>
                    </section>

                @elseif ($tab === 'endorsements')
                    @include('cv.partials.endorsements')

                @elseif ($tab === 'projects')
                    <section class="card p-6" aria-labelledby="add-project-title">
                        <h2 id="add-project-title" class="text-xl">{{ __('Add a project') }}</h2>
                        <p class="mt-1 text-muted">{{ __('Show proof of your work: something you built, designed, researched or led.') }}@if (! $cloudinary) {{ __('Image and video uploads are not switched on for this site yet.') }}@endif</p>
                        <form method="POST" action="{{ route('cvs.projects.store', $cv) }}" enctype="multipart/form-data" class="mt-5 grid gap-4 sm:grid-cols-2">
                            @csrf
                            <x-field name="title" :label="__('Title')" required maxlength="100" />
                            <x-field name="url" :label="__('Link')" type="url" maxlength="300" :hint="__('A live demo, repository or write-up.')" />
                            <x-field class="sm:col-span-2" name="description" :label="__('What you did and what came of it')" type="textarea" rows="4" maxlength="1500" counter />
                            @if ($cloudinary)
                                <x-storage-meter :user="auth()->user()" class="sm:col-span-2" />
                                <x-field name="media" :label="__('Image or short video')" type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                                         :hint="$mediaHint" />
                                <x-field name="media_alt" :label="__('Describe the image or video')" maxlength="200" :hint="__('Read out to people who cannot see it.')" />
                            @endif
                            <div class="sm:col-span-2"><button type="submit" class="btn btn-primary">{{ __('Add project') }}</button></div>
                        </form>
                    </section>

                    @foreach ($cv->projects as $project)
                        <section class="card mt-6 p-6" aria-labelledby="project-{{ $project->id }}">
                            <h2 id="project-{{ $project->id }}" class="text-lg">{{ $project->title }}</h2>
                            @php($bag = 'project-'.$project->id)
                            <x-error-summary :bag="$bag" :prefix="'p'.$project->id.'-'" />
                            <form method="POST" action="{{ route('cvs.projects.update', [$cv, $project]) }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                                @csrf @method('PUT')
                                <x-field name="title" :label="__('Title')" :value="$project->title" required maxlength="100" :field-id="'p'.$project->id.'-title'" :error-bag="$bag" />
                                <x-field name="url" :label="__('Link')" type="url" :value="$project->url" maxlength="300" :field-id="'p'.$project->id.'-url'" :error-bag="$bag" />
                                <x-field class="sm:col-span-2" name="description" :label="__('Description')" type="textarea" rows="3" :value="$project->description" maxlength="1500" :field-id="'p'.$project->id.'-description'" :error-bag="$bag" />
                                @if ($project->media_type)
                                    <label class="flex items-center gap-3 sm:col-span-2"><input type="checkbox" name="remove_media" value="1" class="check"> {{ $project->media_type === 'video' ? __('Remove the video') : __('Remove the image') }}</label>
                                @endif
                                @if ($cloudinary)
                                    <x-field name="media" :label="$project->media_type ? __('Replace the media') : __('Add an image or video')" type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" :field-id="'p'.$project->id.'-media'" :error-bag="$bag" :hint="$mediaHint" />
                                    <x-field name="media_alt" :label="__('Describe the image or video')" :value="$project->media_alt" maxlength="200" :field-id="'p'.$project->id.'-media_alt'" :error-bag="$bag" />
                                @endif
                                <div class="flex flex-wrap gap-3 sm:col-span-2">
                                    <button type="submit" class="btn btn-primary">{{ __('Save project') }}</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('cvs.projects.destroy', [$cv, $project]) }}" class="mt-3" data-confirm="{{ __('Remove this project?') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary">{{ __('Remove project') }}</button>
                            </form>
                        </section>
                    @endforeach

                @elseif ($tab === 'file')
                    <section class="card p-6" aria-labelledby="latex-title">
                        <h2 id="latex-title" class="text-xl">{{ __('Write it in LaTeX') }}</h2>
                        <p class="mt-2 text-muted">{{ __('Prefer full control over the typesetting? Write this CV in LaTeX, compile it to a PDF in your browser and attach it here. Start from a template filled in with this CV\'s content.') }}</p>
                        <a class="btn btn-primary mt-4" href="{{ route('cvs.latex', $cv) }}">{{ $cv->latex_source ? __('Open the LaTeX editor') : __('Start in LaTeX') }}</a>
                    </section>

                    <section class="card mt-8 p-6" aria-labelledby="file-title">
                        <h2 id="file-title" class="text-xl">{{ __('Upload your own CV file') }}</h2>
                        <p class="mt-2 text-muted">{{ __('Already have a CV you like? Upload it as a PDF or Word file. Visitors can open or download it from this CV\'s page, alongside or instead of the content you add here.') }}</p>
                        @if ($cv->document)
                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-raised p-4">
                                <p>
                                    <strong>{{ $cv->document->filename }}</strong>
                                    <span class="text-muted">({{ $cv->document->source === 'latex'
                                        ? __('compiled from LaTeX, :size, updated :time', ['size' => $cv->document->humanSize(), 'time' => $cv->document->updated_at->diffForHumans()])
                                        : __(':size, updated :time', ['size' => $cv->document->humanSize(), 'time' => $cv->document->updated_at->diffForHumans()]) }})</span>
                                </p>
                                <div class="flex gap-2">
                                    <a class="btn btn-sm btn-secondary" href="{{ route('cv.file', $cv) }}">{{ __('Open') }}</a>
                                    <form method="POST" action="{{ route('cvs.document.delete', $cv) }}" data-confirm="{{ __('Remove this file from the CV?') }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-secondary">{{ __('Remove') }}</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                        <x-storage-meter :user="auth()->user()" class="mt-6" />
                        <form method="POST" action="{{ route('cvs.document', $cv) }}" enctype="multipart/form-data" class="mt-6 grid gap-4">
                            @csrf
                            <x-field name="document" :label="$cv->document ? __('Replace the file') : __('Choose a file')" type="file" required
                                     accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                     :hint="__('PDF or Word (.docx), up to :size MB. The file is checked by its contents, not its name.', ['size' => intdiv($limits['document_kb'], 1024)])" />
                            <div><button type="submit" class="btn btn-primary">{{ __('Upload') }}</button></div>
                        </form>
                    </section>

                @elseif ($tab === 'settings')
                    <form method="POST" action="{{ route('cvs.settings', $cv) }}" class="card grid gap-6 p-6">
                        @csrf @method('PUT')
                        <h2 class="text-xl">{{ __('Name and address') }}</h2>
                        <x-field name="title" :label="__('CV name')" :value="$cv->title" required maxlength="80" :hint="__('Only you see this, for example: Software engineering CV')" />
                        <x-field name="slug" :label="__('Web address')" :value="$cv->slug" required maxlength="100"
                                 :hint="__('This CV lives at :address. Use lowercase letters, numbers and hyphens.', ['address' => url('/cv').'/'.$cv->slug])" />

                        <fieldset class="grid gap-3">
                            <legend class="field-label text-xl">{{ __('Who can see it') }}</legend>
                            @foreach (config('vitafolio.visibility') as $value => $label)
                                <label class="flex items-start gap-3 rounded-xl border-2 border-line p-3 has-[:checked]:border-brand has-[:checked]:bg-raised">
                                    <input type="radio" name="visibility" value="{{ $value }}" class="radio mt-0.5" @checked(old('visibility', $cv->visibility) === $value)>
                                    <span>{{ __($label) }}</span>
                                </label>
                            @endforeach
                        </fieldset>

                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="show_email" value="1" class="check mt-1" @checked(old('show_email', $cv->show_email))>
                            <span><strong>{{ __('Show my email address on this CV') }}</strong><br><span class="text-sm text-muted">{{ __('Off by default. When off, people can still message you through a form and your address stays private.') }}</span></span>
                        </label>

                        <fieldset class="grid gap-3">
                            <legend class="field-label text-xl">{{ __('Layout') }}</legend>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach (config('vitafolio.themes') as $value => $label)
                                    <label class="flex items-start gap-3 rounded-xl border-2 border-line p-3 has-[:checked]:border-brand has-[:checked]:bg-raised">
                                        <input type="radio" name="theme" value="{{ $value }}" class="radio mt-0.5" @checked(old('theme', $cv->theme) === $value)>
                                        <span>{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="grid gap-3">
                            <legend class="field-label text-xl">{{ __('Accent colour') }}</legend>
                            <p class="field-hint">{{ __('Every colour is checked for readable contrast in light and dark mode.') }}</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach (config('vitafolio.accents') as $value => $accent)
                                    <label class="accent-{{ $value }} inline-flex items-center gap-2 rounded-xl border-2 border-line px-3 py-2 has-[:checked]:border-brand has-[:checked]:bg-raised">
                                        <input type="radio" name="accent" value="{{ $value }}" class="radio" @checked(old('accent', $cv->accent) === $value)>
                                        <span aria-hidden="true" class="inline-block size-5 rounded-full bg-accent-solid"></span>
                                        <span>{{ __($accent['label']) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="grid gap-3">
                            <legend class="field-label text-xl">{{ __('Font') }}</legend>
                            @foreach (config('vitafolio.fonts') as $value => $label)
                                <label class="flex items-center gap-3 rounded-xl border-2 border-line p-3 has-[:checked]:border-brand has-[:checked]:bg-raised font-cv-{{ $value }}">
                                    <input type="radio" name="font" value="{{ $value }}" class="radio" @checked(old('font', $cv->font) === $value)>
                                    <span>{{ __($label) }}</span>
                                </label>
                            @endforeach
                        </fieldset>

                        <div class="field">
                            <label for="f-language" class="field-label">{{ __('Document language') }}</label>
                            {{-- each language is named in its own script, the same as the language menu --}}
                            <select id="f-language" name="language" class="input sm:max-w-xs" aria-describedby="f-language-hint">
                                @foreach (\App\Support\Locales::ALL as $code => $language)
                                    <option value="{{ $code }}" lang="{{ $language['html'] }}" @selected(old('language', $cv->documentLocale()) === $code)>{{ $language['name'] }}</option>
                                @endforeach
                            </select>
                            <p id="f-language-hint" class="field-hint">{{ __('The language of the fixed labels on this CV, such as its section headings, and of its PDF and Word files. Arabic and Urdu read right to left. What you write is never translated.') }}</p>
                        </div>

                        <div><button type="submit" class="btn btn-primary">{{ __('Save settings') }}</button></div>
                    </form>

                    <section class="card mt-8 border-bad p-6" aria-labelledby="delete-title">
                        <h2 id="delete-title" class="text-xl text-bad">{{ __('Delete this CV') }}</h2>
                        <p class="mt-2 text-muted">{{ __('This removes the CV, its projects, file, LaTeX source and view history. Your profile and other CVs are not affected. This cannot be undone.') }}</p>
                        <form method="POST" action="{{ route('cvs.destroy', $cv) }}" class="mt-4 grid gap-4 sm:max-w-md">
                            @csrf @method('DELETE')
                            <x-field name="confirm_title" :label="__('Type :title to confirm', ['title' => $cv->title])" required autocomplete="off" />
                            <div><button type="submit" class="btn btn-danger">{{ __('Delete this CV') }}</button></div>
                        </form>
                    </section>

                @elseif ($tab === 'import')
                    <section class="card p-6" aria-labelledby="import-title">
                        <h2 id="import-title" class="text-xl">{{ __('Import from JSON Resume') }}</h2>
                        <p class="mt-2 text-muted">
                            {!! __(':link is an open format many CV tools can export.', ['link' => '<a href="https://jsonresume.org/" target="_blank" rel="noopener">JSON Resume'.$newTab.'</a>']) !!}
                            {{ __('Importing fills in this CV’s headline, profile, experience, education and skills, replacing what is there. Your location and links are added to your profile only where it is still empty.') }}
                        </p>
                        <form method="POST" action="{{ route('cvs.import', $cv) }}" enctype="multipart/form-data" class="mt-6 grid gap-4" data-confirm="{{ __('Importing replaces the matching sections of this CV. Continue?') }}">
                            @csrf
                            <x-field name="resume" :label="__('Upload a resume.json file')" type="file" accept=".json,application/json" />
                            <p class="text-sm text-muted">{!! __('Have a PDF or Word CV instead? Upload it on the :tab.', ['tab' => '<a href="'.e(route('cvs.edit', [$cv, 'file'])).'">'.e(__('File and LaTeX tab')).'</a>']) !!}</p>
                            <x-field name="resume_text" :label="__('Or paste the JSON')" type="textarea" rows="6" />
                            <div><button type="submit" class="btn btn-primary">{{ __('Import') }}</button></div>
                        </form>
                    </section>
                    <section class="card mt-8 p-6" aria-labelledby="export-title">
                        <h2 id="export-title" class="text-xl">{{ __('Export') }}</h2>
                        <p class="mt-2 text-muted">{{ __('Download this CV as JSON Resume to use it in other tools. PDF and Word downloads are on the CV page too.') }}</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a class="btn btn-secondary" href="{{ route('cvs.export', $cv) }}">{{ __('Download JSON Resume') }}</a>
                            <a class="btn btn-secondary" href="{{ route('cv.pdf', $cv) }}">{{ __('Download PDF') }}</a>
                            <a class="btn btn-secondary" href="{{ route('cv.word', $cv) }}">{{ __('Download as Word') }}</a>
                            @if ($cv->hasCoverLetter())
                                <a class="btn btn-secondary" href="{{ route('cv.letter.pdf', $cv) }}">{{ __('Download cover letter as PDF') }}</a>
                                <a class="btn btn-secondary" href="{{ route('cv.letter.word', $cv) }}">{{ __('Download cover letter as Word') }}</a>
                            @endif
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-5" aria-label="{{ __('Checklist') }}">
                @php($checklist = $cv->completeness())
                @php($done = collect($checklist)->filter()->count())
                <section class="card p-5">
                    <h2 class="text-base">{{ __('Completeness') }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ __(':done of :total filled in', ['done' => $done, 'total' => count($checklist)]) }}</p>
                    <progress class="mt-3 h-2 w-full" value="{{ $done }}" max="{{ count($checklist) }}">{{ __(':done of :total', ['done' => $done, 'total' => count($checklist)]) }}</progress>
                    <ul class="mt-4 space-y-1.5 text-sm">
                        @foreach ($checklist as $item => $filled)
                            <li class="flex gap-2 {{ $filled ? '' : 'text-muted' }}">
                                <span aria-hidden="true" class="w-4 font-bold {{ $filled ? 'text-ok' : '' }}">{{ $filled ? '✓' : '○' }}</span>
                                {{ __($item) }}<span class="sr-only">{{ $filled ? __(': done') : __(': not yet') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
                <section class="card p-5">
                    <h2 class="text-base">{{ __('More actions') }}</h2>
                    <div class="mt-3 grid gap-2">
                        <form method="POST" action="{{ route('cvs.duplicate', $cv) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">{{ __('Duplicate this CV') }}</button>
                        </form>
                        <a class="btn btn-secondary" href="{{ route('dashboard', ['cv' => $cv->slug]) }}#stats">{{ __('View statistics') }}</a>
                        <a class="btn btn-secondary" href="{{ route('profile.edit') }}">{{ __('Edit profile') }}</a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
