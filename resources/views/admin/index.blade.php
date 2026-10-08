<x-layouts.app :title="__('Moderation')" noindex>
    <div class="container-page py-8">
        <h1 class="text-3xl">{{ __('Moderation') }}</h1>
        <p class="mt-1 text-muted">{{ __('Reports from visitors, hidden CVs, hidden endorsements and account actions. Every action here is reversible except removing a photo.') }}</p>
        <p class="mt-3"><a class="btn btn-secondary btn-sm" href="{{ route('admin.support') }}">{{ __('Support queue') }}</a></p>

        <dl class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($stats as $label => $value)
                <div class="card p-4">
                    <dt class="text-sm text-muted">{{ __($label) }}</dt>
                    <dd class="mt-1 text-2xl font-semibold">{{ number_format($value) }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="mt-10" aria-labelledby="reports-title">
            <h2 id="reports-title" class="text-2xl">{{ __('Open reports') }}</h2>
            @forelse ($reports as $report)
                @php($cv = $report->cv)
                <article class="card mt-4 grid gap-4 p-5 md:grid-cols-[1fr_auto]" aria-labelledby="report-{{ $report->id }}">
                    <div>
                        <h3 id="report-{{ $report->id }}" class="text-lg">
                            @if ($cv)
                                <a href="{{ route('cv.show', $cv) }}">{{ $cv->title }}</a>
                                <span class="font-normal text-muted">{{ __('by :name', ['name' => $cv->user->name]) }} (<span dir="ltr">{{ '@'.$cv->user->handle }}</span>)</span>
                            @else
                                {{ __('A CV that has since been deleted') }}
                            @endif
                        </h3>
                        <p class="mt-2">
                            <span class="badge">{{ __(\App\Models\Report::REASONS[$report->reason] ?? $report->reason) }}</span>
                            <span class="ms-2 text-sm text-muted">{{ __('Reported :time', ['time' => $report->created_at->diffForHumans()]) }}</span>
                            @if ($cv?->hidden_at)<span class="badge ms-2">{{ __('Already hidden') }}</span>@endif
                            @if ($cv?->user->isSuspended())<span class="badge ms-2">{{ __('Account suspended') }}</span>@endif
                        </p>
                        @if ($report->endorsement)
                            <div class="mt-3 rounded-xl border border-line bg-raised p-4">
                                <p class="text-sm font-semibold">{{ __('Reported endorsement by :name', ['name' => $report->endorsement->endorser->name]) }}@if ($report->endorsement->endorser->handle) (<span dir="ltr">{{ '@'.$report->endorsement->endorser->handle }}</span>)@endif
                                    @if ($report->endorsement->hidden_at)<span class="badge ms-2">{{ __('Already hidden') }}</span>@endif</p>
                                <p class="mt-2 whitespace-pre-line" dir="auto">{{ $report->endorsement->body }}</p>
                            </div>
                        @endif
                        @if ($report->details)
                            <blockquote class="mt-3 border-s-4 border-line-strong ps-4 whitespace-pre-line text-muted" dir="auto">{{ $report->details }}</blockquote>
                        @endif
                    </div>
                    <div class="flex flex-wrap content-start gap-2 md:flex-col">
                        @if ($report->endorsement && ! $report->endorsement->hidden_at)
                            <form method="POST" action="{{ route('admin.endorsements.hide', $report->endorsement) }}" data-confirm="{{ __('Hide this endorsement from everyone? This closes its open reports.') }}">
                                @csrf
                                <button class="btn btn-sm btn-primary w-full">{{ __('Hide endorsement') }}</button>
                            </form>
                        @endif
                        @if ($report->endorsement && ! $report->endorsement->endorser->isAdmin())
                            <form method="POST" action="{{ route('admin.suspend', $report->endorsement->endorser) }}"
                                  data-confirm="{{ $report->endorsement->endorser->isSuspended() ? __('Reinstate :name, who wrote this endorsement?', ['name' => $report->endorsement->endorser->name]) : __('Suspend :name, who wrote this endorsement?', ['name' => $report->endorsement->endorser->name]) }}">
                                @csrf
                                <button class="btn btn-sm btn-secondary w-full">{{ $report->endorsement->endorser->isSuspended() ? __('Reinstate the endorser') : __('Suspend the endorser') }}</button>
                            </form>
                        @endif
                        @if ($cv && ! $cv->hidden_at)
                            <form method="POST" action="{{ route('admin.hide', $cv) }}" data-confirm="{{ __('Hide this CV from everyone except its owner? This closes its open reports.') }}">
                                @csrf
                                <button class="btn btn-sm btn-primary w-full">{{ __('Hide CV') }}</button>
                            </form>
                        @endif
                        @if ($cv?->user->hasAvatar())
                            <form method="POST" action="{{ route('admin.avatar.remove', $cv->user) }}" data-confirm="{{ __('Remove the photo of :name? This cannot be undone.', ['name' => $cv->user->name]) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-secondary w-full">{{ __('Remove photo') }}</button>
                            </form>
                        @endif
                        @if ($cv && ! $cv->user->isAdmin())
                            <form method="POST" action="{{ route('admin.suspend', $cv->user) }}"
                                  data-confirm="{{ $cv->user->isSuspended() ? __('Reinstate :name?', ['name' => $cv->user->name]) : __('Suspend :name? They are signed out everywhere and their CVs disappear.', ['name' => $cv->user->name]) }}">
                                @csrf
                                <button class="btn btn-sm btn-secondary w-full">{{ $cv->user->isSuspended() ? __('Reinstate account') : __('Suspend account') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.dismiss', $report) }}">
                            @csrf
                            <button class="btn btn-sm btn-secondary w-full">{{ __('Dismiss report') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="card mt-4 p-5 text-muted">{{ __('No open reports. Everything reported so far has been dealt with.') }}</p>
            @endforelse
            <div class="mt-6">{{ $reports->links() }}</div>
        </section>

        <section class="mt-12" aria-labelledby="hidden-endorsements-title">
            <h2 id="hidden-endorsements-title" class="text-2xl">{{ __('Hidden endorsements') }}</h2>
            @if ($hiddenEndorsements->isEmpty())
                <p class="card mt-4 p-5 text-muted">{{ __('No endorsements are hidden.') }}</p>
            @else
                <div class="card mt-4 overflow-x-auto p-0">
                    <table class="w-full text-start">
                        <caption class="sr-only">{{ __('The 20 most recently hidden endorsements') }}</caption>
                        <thead class="border-b border-line text-sm text-muted">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">{{ __('Written by') }}</th>
                                <th scope="col" class="px-5 py-3 font-semibold">{{ __('On the CV') }}</th>
                                <th scope="col" class="px-5 py-3 font-semibold">{{ __('Hidden') }}</th>
                                <th scope="col" class="px-5 py-3 font-semibold"><span class="sr-only">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hiddenEndorsements as $endorsement)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-5 py-3">{{ $endorsement->endorser->name }}</td>
                                    <td class="px-5 py-3"><a href="{{ route('cv.show', $endorsement->cv) }}">{{ $endorsement->cv->title }}</a></td>
                                    <td class="px-5 py-3 text-muted">{{ $endorsement->hidden_at->diffForHumans() }}</td>
                                    <td class="px-5 py-3 text-end">
                                        <form method="POST" action="{{ route('admin.endorsements.restore', $endorsement) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-secondary">{{ __('Restore') }}<span class="sr-only"> {{ __('the endorsement by :name', ['name' => $endorsement->endorser->name]) }}</span></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="mt-12" aria-labelledby="hidden-title">
            <h2 id="hidden-title" class="text-2xl">{{ __('Hidden CVs') }}</h2>
            @if ($hidden->isEmpty())
                <p class="card mt-4 p-5 text-muted">{{ __('No CVs are hidden.') }}</p>
            @else
                <div class="card mt-4 overflow-x-auto p-0">
                    <table class="w-full text-start">
                        <caption class="sr-only">{{ __('The 20 most recently hidden CVs') }}</caption>
                        <thead class="border-b border-line text-sm text-muted">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">{{ __('CV') }}</th>
                                <th scope="col" class="px-5 py-3 font-semibold">{{ __('Owner') }}</th>
                                <th scope="col" class="px-5 py-3 font-semibold">{{ __('Hidden') }}</th>
                                <th scope="col" class="px-5 py-3 font-semibold"><span class="sr-only">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hidden as $cv)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-5 py-3"><a href="{{ route('cv.show', $cv) }}">{{ $cv->title }}</a></td>
                                    <td class="px-5 py-3">{{ $cv->user->name }}</td>
                                    <td class="px-5 py-3 text-muted">{{ $cv->hidden_at->diffForHumans() }}</td>
                                    <td class="px-5 py-3 text-end">
                                        <form method="POST" action="{{ route('admin.restore', $cv) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-secondary">{{ __('Restore') }}<span class="sr-only"> {{ $cv->title }}</span></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
