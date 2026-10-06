@props(['cv', 'isOwner', 'headingClass'])
{{-- only approved endorsements reach this list, only ever on a cv the viewer may already see --}}
@if ($cv->endorsements->isNotEmpty())
    <section id="endorsements" class="card p-6 sm:p-8 accent-{{ $cv->accent }} font-cv-{{ $cv->font }}" aria-labelledby="endorsements-title">
        <h2 id="endorsements-title" class="mb-1 {{ $headingClass }}">Endorsements</h2>
        <p class="text-sm text-muted">From people who know {{ $cv->user->firstName() }}'s work. {{ $cv->user->firstName() }} approves each one before it shows.</p>
        <ul class="mt-5 grid gap-5">
            @foreach ($cv->endorsements as $endorsement)
                @php($endorser = $endorsement->endorser)
                <li class="rounded-xl border border-line bg-raised p-5">
                    <article aria-labelledby="endorsement-{{ $endorsement->id }}">
                        <h3 id="endorsement-{{ $endorsement->id }}" class="text-base font-semibold">
                            @if ($endorser->handle && $endorser->profileVisibleTo(auth()->user()))
                                <a href="{{ route('profile.show', $endorser->handle) }}">{{ $endorser->name }}</a>
                            @else
                                {{ $endorser->name }}
                            @endif
                        </h3>
                        <p class="text-sm text-muted">{{ $endorsement->relationshipLabel() }}@if ($endorsement->context). {{ $endorsement->context }}@endif</p>
                        <blockquote class="mt-3 border-l-4 border-line-strong pl-4 whitespace-pre-line">{{ $endorsement->body }}</blockquote>
                        @unless ($isOwner || auth()->id() === $endorser->id)
                            <details class="mt-3 no-print">
                                <summary class="cursor-pointer text-sm">Report this endorsement<span class="sr-only"> from {{ $endorser->name }}</span></summary>
                                <x-endorsement-report :cv="$cv" :endorsement="$endorsement" />
                            </details>
                        @endunless
                    </article>
                </li>
            @endforeach
        </ul>
    </section>
@endif
