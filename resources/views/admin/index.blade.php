<x-layouts.app title="Moderation" noindex>
    <div class="container-page py-8">
        <h1 class="text-3xl">Moderation</h1>
        <p class="mt-1 text-muted">Reports from visitors, hidden CVs, hidden endorsements and account actions. Every action here is reversible except removing a photo.</p>

        <dl class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($stats as $label => $value)
                <div class="card p-4">
                    <dt class="text-sm text-muted">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-semibold">{{ number_format($value) }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="mt-10" aria-labelledby="reports-title">
            <h2 id="reports-title" class="text-2xl">Open reports</h2>
            @forelse ($reports as $report)
                @php($cv = $report->cv)
                <article class="card mt-4 grid gap-4 p-5 md:grid-cols-[1fr_auto]" aria-labelledby="report-{{ $report->id }}">
                    <div>
                        <h3 id="report-{{ $report->id }}" class="text-lg">
                            @if ($cv)
                                <a href="{{ route('cv.show', $cv) }}">{{ $cv->title }}</a>
                                <span class="font-normal text-muted">by {{ $cv->user->name }} ({{ '@'.$cv->user->handle }})</span>
                            @else
                                A CV that has since been deleted
                            @endif
                        </h3>
                        <p class="mt-2">
                            <span class="badge">{{ \App\Models\Report::REASONS[$report->reason] ?? $report->reason }}</span>
                            <span class="ml-2 text-sm text-muted">Reported {{ $report->created_at->diffForHumans() }}</span>
                            @if ($cv?->hidden_at)<span class="badge ml-2">Already hidden</span>@endif
                            @if ($cv?->user->isSuspended())<span class="badge ml-2">Account suspended</span>@endif
                        </p>
                        @if ($report->endorsement)
                            <div class="mt-3 rounded-xl border border-line bg-raised p-4">
                                <p class="text-sm font-semibold">Reported endorsement by {{ $report->endorsement->endorser->name }}@if ($report->endorsement->endorser->handle) ({{ '@'.$report->endorsement->endorser->handle }})@endif
                                    @if ($report->endorsement->hidden_at)<span class="badge ml-2">Already hidden</span>@endif</p>
                                <p class="mt-2 whitespace-pre-line">{{ $report->endorsement->body }}</p>
                            </div>
                        @endif
                        @if ($report->details)
                            <blockquote class="mt-3 border-l-4 border-line-strong pl-4 whitespace-pre-line text-muted">{{ $report->details }}</blockquote>
                        @endif
                    </div>
                    <div class="flex flex-wrap content-start gap-2 md:flex-col">
                        @if ($report->endorsement && ! $report->endorsement->hidden_at)
                            <form method="POST" action="{{ route('admin.endorsements.hide', $report->endorsement) }}" data-confirm="Hide this endorsement from everyone? This closes its open reports.">
                                @csrf
                                <button class="btn btn-sm btn-primary w-full">Hide endorsement</button>
                            </form>
                        @endif
                        @if ($report->endorsement && ! $report->endorsement->endorser->isAdmin())
                            <form method="POST" action="{{ route('admin.suspend', $report->endorsement->endorser) }}"
                                  data-confirm="{{ $report->endorsement->endorser->isSuspended() ? 'Reinstate' : 'Suspend' }} {{ $report->endorsement->endorser->name }}, who wrote this endorsement?">
                                @csrf
                                <button class="btn btn-sm btn-secondary w-full">{{ $report->endorsement->endorser->isSuspended() ? 'Reinstate the endorser' : 'Suspend the endorser' }}</button>
                            </form>
                        @endif
                        @if ($cv && ! $cv->hidden_at)
                            <form method="POST" action="{{ route('admin.hide', $cv) }}" data-confirm="Hide this CV from everyone except its owner? This closes its open reports.">
                                @csrf
                                <button class="btn btn-sm btn-primary w-full">Hide CV</button>
                            </form>
                        @endif
                        @if ($cv?->user->hasAvatar())
                            <form method="POST" action="{{ route('admin.avatar.remove', $cv->user) }}" data-confirm="Remove {{ $cv->user->name }}'s photo? This cannot be undone.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-secondary w-full">Remove photo</button>
                            </form>
                        @endif
                        @if ($cv && ! $cv->user->isAdmin())
                            <form method="POST" action="{{ route('admin.suspend', $cv->user) }}"
                                  data-confirm="{{ $cv->user->isSuspended() ? 'Reinstate' : 'Suspend' }} {{ $cv->user->name }}? {{ $cv->user->isSuspended() ? '' : 'They are signed out everywhere and their CVs disappear.' }}">
                                @csrf
                                <button class="btn btn-sm btn-secondary w-full">{{ $cv->user->isSuspended() ? 'Reinstate account' : 'Suspend account' }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.dismiss', $report) }}">
                            @csrf
                            <button class="btn btn-sm btn-secondary w-full">Dismiss report</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="card mt-4 p-5 text-muted">No open reports. Everything reported so far has been dealt with.</p>
            @endforelse
            <div class="mt-6">{{ $reports->links() }}</div>
        </section>

        <section class="mt-12" aria-labelledby="hidden-endorsements-title">
            <h2 id="hidden-endorsements-title" class="text-2xl">Hidden endorsements</h2>
            @if ($hiddenEndorsements->isEmpty())
                <p class="card mt-4 p-5 text-muted">No endorsements are hidden.</p>
            @else
                <div class="card mt-4 overflow-x-auto p-0">
                    <table class="w-full text-left">
                        <caption class="sr-only">The 20 most recently hidden endorsements</caption>
                        <thead class="border-b border-line text-sm text-muted">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">Written by</th>
                                <th scope="col" class="px-5 py-3 font-semibold">On the CV</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Hidden</th>
                                <th scope="col" class="px-5 py-3 font-semibold"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hiddenEndorsements as $endorsement)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-5 py-3">{{ $endorsement->endorser->name }}</td>
                                    <td class="px-5 py-3"><a href="{{ route('cv.show', $endorsement->cv) }}">{{ $endorsement->cv->title }}</a></td>
                                    <td class="px-5 py-3 text-muted">{{ $endorsement->hidden_at->diffForHumans() }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.endorsements.restore', $endorsement) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-secondary">Restore<span class="sr-only"> the endorsement by {{ $endorsement->endorser->name }}</span></button>
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
            <h2 id="hidden-title" class="text-2xl">Hidden CVs</h2>
            @if ($hidden->isEmpty())
                <p class="card mt-4 p-5 text-muted">No CVs are hidden.</p>
            @else
                <div class="card mt-4 overflow-x-auto p-0">
                    <table class="w-full text-left">
                        <caption class="sr-only">The 20 most recently hidden CVs</caption>
                        <thead class="border-b border-line text-sm text-muted">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">CV</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Owner</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Hidden</th>
                                <th scope="col" class="px-5 py-3 font-semibold"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hidden as $cv)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-5 py-3"><a href="{{ route('cv.show', $cv) }}">{{ $cv->title }}</a></td>
                                    <td class="px-5 py-3">{{ $cv->user->name }}</td>
                                    <td class="px-5 py-3 text-muted">{{ $cv->hidden_at->diffForHumans() }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.restore', $cv) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-secondary">Restore<span class="sr-only"> {{ $cv->title }}</span></button>
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
