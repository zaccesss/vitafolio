@props(['cv', 'isOwner', 'letter' => false])
{{-- owners and admins are told who else can open this address, on the cv and its letter alike --}}
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
        @if ($isOwner)
            <a class="btn btn-sm btn-secondary" href="{{ $letter ? route('cvs.edit', [$cv, 'letter']) : route('cvs.edit', $cv) }}">{{ $letter ? 'Edit the cover letter' : 'Edit this CV' }}</a>
        @endif
    </div>
@endif
