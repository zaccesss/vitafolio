@props(['cv', 'isOwner', 'letter' => false])
{{-- owners and admins are told who else can open this address, on the cv and its letter alike --}}
@if ($isOwner || auth()->user()?->isAdmin())
    <div class="alert alert-info mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
        <p>
            @if ($cv->hidden_at)
                <strong>{{ __('Hidden by a moderator.') }}</strong> {{ __('Only you and admins can see this CV.') }}
            @elseif ($cv->visibility === 'private')
                <strong>{{ __('Private.') }}</strong> {{ __('Only you can see this CV.') }}
            @elseif ($cv->visibility === 'unlisted')
                <strong>{{ __('Unlisted.') }}</strong> {{ __('Anyone with the link can see it, but it is not in the directory.') }}
            @else
                <strong>{{ __('Public.') }}</strong> {{ __('Listed in the directory.') }}
            @endif
        </p>
        @if ($isOwner)
            <a class="btn btn-sm btn-secondary" href="{{ $letter ? route('cvs.edit', [$cv, 'letter']) : route('cvs.edit', $cv) }}">{{ $letter ? __('Edit the cover letter') : __('Edit this CV') }}</a>
        @endif
    </div>
@endif
