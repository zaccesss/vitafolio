@props(['cv', 'isOwner', 'mine' => null, 'pending' => 0])
@php($first = $cv->user->firstName())
{{-- owners hear what waits for them; anyone else may endorse a cv that other people can see --}}
@if ($isOwner)
    @if ($pending > 0)
        <section class="card p-5" aria-labelledby="endorse-title">
            <h2 id="endorse-title" class="text-base">Endorsements</h2>
            <p class="mt-1 text-sm">{{ $pending }} {{ $pending === 1 ? 'endorsement is' : 'endorsements are' }} waiting for your approval.</p>
            <a class="btn btn-sm btn-secondary mt-3" href="{{ route('cvs.edit', [$cv, 'endorsements']) }}">Review endorsements</a>
        </section>
    @endif
@elseif ($cv->isVisibleTo(null))
    <section id="endorse" class="card p-5" aria-labelledby="endorse-title">
        <h2 id="endorse-title" class="text-base">{{ $mine ? 'Your endorsement' : 'Endorse '.$first }}</h2>
        @guest
            <p class="mt-1 text-sm text-muted">Worked or studied with {{ $first }}? <a href="{{ route('login') }}">Sign in</a> to write a short endorsement. It shows once {{ $first }} approves it.</p>
        @else
            @if ($mine)
                <p class="mt-2"><span class="badge">{{ $mine->hidden_at ? 'Hidden by a moderator' : \App\Models\Endorsement::STATUSES[$mine->status] }}</span></p>
                <p class="mt-2 text-sm text-muted">Editing it means {{ $first }} approves the new wording before it shows again.</p>
            @else
                <p class="mt-1 text-sm text-muted">A few words about working or studying with {{ $first }}. It shows on this CV once {{ $first }} approves it. Your name is shown with it.</p>
            @endif
            <x-error-summary bag="endorsement" />
            <form method="POST" action="{{ $mine ? route('cv.endorsements.update', [$cv, $mine]) : route('cv.endorsements.store', $cv) }}" class="mt-4 grid gap-4">
                @csrf
                @if ($mine) @method('PUT') @endif
                <x-field name="relationship" label="How you know them" type="select" required error-bag="endorsement" :value="$mine?->relationship"
                         :options="['' => 'Choose one'] + \App\Models\Endorsement::RELATIONSHIPS" />
                <x-field name="context" label="Role or context" maxlength="120" error-bag="endorsement" :value="$mine?->context"
                         hint="For example: Team lead on the payments app at Acme, 2025" />
                <x-field name="body" label="Endorsement" type="textarea" rows="5" required minlength="20" maxlength="{{ \App\Models\Endorsement::MAX_LENGTH }}" counter error-bag="endorsement" :value="$mine?->body"
                         :hint="'Between 20 and '.\App\Models\Endorsement::MAX_LENGTH.' characters.'" />
                <x-turnstile />
                <button type="submit" class="btn btn-primary">{{ $mine ? 'Save changes' : 'Send endorsement' }}</button>
            </form>
            @if ($mine && ! $mine->hidden_at)
                <form method="POST" action="{{ route('cv.endorsements.destroy', [$cv, $mine]) }}" class="mt-3" data-confirm="Withdraw your endorsement? It is deleted and no longer shows on this CV.">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary w-full">Withdraw endorsement</button>
                </form>
            @endif
        @endguest
    </section>
@endif
