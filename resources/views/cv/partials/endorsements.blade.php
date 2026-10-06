@php
    $groups = [
        'pending' => ['Waiting for approval', 'Nobody else sees these until you approve them.'],
        'approved' => ['Shown on the CV', 'Anyone who can open this CV sees these.'],
        'hidden' => ['Not shown', 'Only you can see these. Approve one to show it.'],
    ];
    $byStatus = $cv->endorsements->groupBy('status');
@endphp
<section class="card p-6" aria-labelledby="endorsements-title">
    <h2 id="endorsements-title" class="text-xl">Endorsements</h2>
    <p class="mt-1 text-muted">People who worked or studied with you can endorse this CV from its page. Nothing shows until you approve it. If someone edits an endorsement you approved, it waits for you again. Endorsements follow this CV's privacy setting.</p>
    @if ($cv->endorsements->isEmpty())
        <p class="mt-4 text-muted">No endorsements yet. Share this CV's link with people who know your work.</p>
    @endif
</section>

@foreach ($groups as $status => [$heading, $explainer])
    @if ($byStatus->has($status))
        <section class="mt-8" aria-labelledby="endorsements-{{ $status }}">
            <h3 id="endorsements-{{ $status }}" class="text-lg">{{ $heading }} <span class="text-muted">({{ $byStatus[$status]->count() }})</span></h3>
            <p class="text-sm text-muted">{{ $explainer }}</p>
            <ul class="mt-4 grid gap-4">
                @foreach ($byStatus[$status] as $endorsement)
                    @php($endorser = $endorsement->endorser)
                    <li class="card p-5">
                        <article aria-labelledby="owner-endorsement-{{ $endorsement->id }}">
                            <h4 id="owner-endorsement-{{ $endorsement->id }}" class="text-base font-semibold">{{ $endorser->name }}</h4>
                            <p class="text-sm text-muted">
                                {{ $endorsement->relationshipLabel() }}@if ($endorsement->context). {{ $endorsement->context }}@endif.
                                Sent {{ $endorsement->created_at->diffForHumans() }}@if ($endorsement->updated_at->gt($endorsement->created_at)), edited {{ $endorsement->updated_at->diffForHumans() }}@endif.
                            </p>
                            @if ($endorsement->hidden_at)<p class="mt-2"><span class="badge">Hidden by a moderator</span></p>@endif
                            <blockquote class="mt-3 border-l-4 border-line-strong pl-4 whitespace-pre-line">{{ $endorsement->body }}</blockquote>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($status !== 'approved')
                                    <form method="POST" action="{{ route('cvs.endorsements.decide', [$cv, $endorsement]) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-sm btn-primary">Approve<span class="sr-only"> the endorsement from {{ $endorser->name }}</span></button>
                                    </form>
                                @endif
                                @if ($status !== 'hidden')
                                    <form method="POST" action="{{ route('cvs.endorsements.decide', [$cv, $endorsement]) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="hidden">
                                        <button type="submit" class="btn btn-sm btn-secondary">Hide<span class="sr-only"> the endorsement from {{ $endorser->name }}</span></button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('cv.endorsements.destroy', [$cv, $endorsement]) }}" data-confirm="Delete the endorsement from {{ $endorser->name }}? This cannot be undone. They can write a new one.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-secondary">Delete<span class="sr-only"> the endorsement from {{ $endorser->name }}</span></button>
                                </form>
                            </div>
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm">Report it to a moderator<span class="sr-only">: the endorsement from {{ $endorser->name }}</span></summary>
                                <x-endorsement-report :cv="$cv" :endorsement="$endorsement" />
                            </details>
                        </article>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endforeach
