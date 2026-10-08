@php($labels = \App\Models\Application::statusLabels())
<x-layouts.app :title="__('My applications')" noindex>
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl">{{ __('My applications') }}</h1>
                <p class="mt-1 max-w-3xl text-muted">{{ __('Keep track of every role you are applying for: ones saved from the Jobs page and ones you found anywhere else. Only you can see this page.') }}</p>
            </div>
            <a class="btn btn-secondary" href="{{ route('jobs') }}">{{ __('Find jobs') }}</a>
        </div>

        <nav class="mt-6 flex gap-1 overflow-x-auto border-b-2 border-line" aria-label="{{ __('Application status') }}">
            <a class="tab-link" href="{{ route('applications.index') }}" @if ($status === '') aria-current="page" @endif>{{ __('All') }} <span class="ms-1 text-sm font-normal text-muted">{{ $counts->sum() }}</span></a>
            @foreach ($labels as $value => $label)
                <a class="tab-link" href="{{ route('applications.index', ['status' => $value]) }}" @if ($status === $value) aria-current="page" @endif>{{ $label }} <span class="ms-1 text-sm font-normal text-muted">{{ $counts[$value] ?? 0 }}</span></a>
            @endforeach
        </nav>

        <details class="card mt-6 p-5" @if ($errors->any()) open @endif>
            <summary class="cursor-pointer font-semibold">{{ __('Add an application from somewhere else') }}</summary>
            <x-error-summary />
            <form method="POST" action="{{ route('applications.store') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf
                <x-field name="title" :label="__('Role')" required maxlength="200" />
                <x-field name="company" :label="__('Employer')" maxlength="160" />
                <x-field name="location" :label="__('Location')" maxlength="160" />
                <x-field name="url" :label="__('Link to the advert')" type="url" maxlength="500" />
                <x-field name="status" :label="__('Status')" type="select" :options="$labels" />
                <x-field name="deadline" :label="__('Closing date')" type="date" />
                <x-field name="notes" :label="__('Notes')" type="textarea" rows="3" maxlength="5000" class="md:col-span-2" />
                <div class="md:col-span-2"><button type="submit" class="btn btn-primary">{{ __('Add application') }}</button></div>
            </form>
        </details>

        @if ($applications->isEmpty())
            <p class="card mt-6 p-6 text-muted">{{ $status === '' ? __('Nothing here yet. Save a role from the Jobs page or add one above.') : __('No applications with this status.') }}</p>
        @else
            <ul class="mt-6 grid gap-3">
                @foreach ($applications as $application)
                    <li>
                        <article class="card p-4 sm:p-5" aria-labelledby="app-{{ $application->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 id="app-{{ $application->id }}" class="text-lg leading-snug">
                                        @if ($application->url)<a href="{{ $application->url }}" target="_blank" rel="noopener nofollow">{{ $application->title }}<x-new-tab /></a>@else{{ $application->title }}@endif
                                    </h2>
                                    <p class="mt-0.5 text-sm">
                                        @if ($application->company)<span class="font-semibold">{{ $application->company }}</span>@endif
                                        @if ($application->location)<span class="text-muted"> · {{ $application->location }}</span>@endif
                                    </p>
                                    <p class="mt-1 flex flex-wrap gap-x-4 text-sm text-muted">
                                        @if ($application->applied_on)<span>{{ __('Applied :date', ['date' => $application->applied_on->translatedFormat('j M Y')]) }}</span>@endif
                                        @if ($application->deadline)<span class="{{ $application->deadline->isPast() ? '' : 'font-semibold text-ink' }}">{{ __('Closes :date', ['date' => $application->deadline->translatedFormat('j M Y')]) }}</span>@endif
                                    </p>
                                </div>
                                <span class="badge">{{ $labels[$application->status] }}</span>
                            </div>
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-semibold">{{ __('Update') }}</summary>
                                <form method="POST" action="{{ route('applications.update', $application) }}" class="mt-3 grid gap-3 md:grid-cols-3">
                                    @csrf @method('PATCH')
                                    <x-field name="status" :label="__('Status')" type="select" :options="$labels" :value="$application->status" field-id="status-{{ $application->id }}" />
                                    <x-field name="applied_on" :label="__('Applied on')" type="date" :value="$application->applied_on?->toDateString()" field-id="applied-{{ $application->id }}" />
                                    <x-field name="deadline" :label="__('Closing date')" type="date" :value="$application->deadline?->toDateString()" field-id="deadline-{{ $application->id }}" />
                                    <x-field name="notes" :label="__('Notes')" type="textarea" rows="3" maxlength="5000" :value="$application->notes" field-id="notes-{{ $application->id }}" class="md:col-span-3" />
                                    <div class="flex flex-wrap gap-2 md:col-span-3">
                                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Save changes') }}</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('applications.destroy', $application) }}" class="mt-2">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm">{{ __('Remove') }}</button>
                                </form>
                            </details>
                            @if ($application->notes)<p class="mt-2 whitespace-pre-line text-sm" dir="auto">{{ \Illuminate\Support\Str::limit($application->notes, 300) }}</p>@endif
                        </article>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $applications->links() }}</div>
        @endif
    </div>
</x-layouts.app>
