@php($statuses = \App\Models\SupportTicket::statusLabels())
<x-layouts.app :title="$ticket->reference().' · '.$ticket->subject" noindex>
    <div class="container-page max-w-3xl py-10">
        <p class="text-sm text-muted">{{ $ticket->reference() }} · {{ \App\Models\SupportTicket::categoryLabels()[$ticket->category] ?? $ticket->category }}</p>
        <div class="mt-1 flex flex-wrap items-start justify-between gap-3">
            <h1 class="text-3xl">{{ $ticket->subject }}</h1>
            <span class="badge">{{ $statuses[$ticket->status] }}</span>
        </div>
        @if ($staff)
            <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="mt-4 flex flex-wrap items-end gap-2">
                @csrf
                <label for="ticket-status" class="field-label">{{ __('Status') }}</label>
                <select id="ticket-status" name="status" class="input w-auto">
                    @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>@endforeach
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('Update') }}</button>
                <span class="text-sm text-muted">{{ $ticket->name }} · <span dir="ltr">{{ $ticket->email }}</span></span>
            </form>
        @endif

        <ol class="mt-8 grid gap-4" aria-label="{{ __('Conversation') }}">
            @foreach ($ticket->messages as $message)
                <li @if ($loop->last) id="latest" @endif>
                    <article class="card p-5 {{ $message->from_staff ? 'border-s-4 border-s-brand' : '' }}" aria-labelledby="m-{{ $message->id }}">
                        <h2 id="m-{{ $message->id }}" class="text-sm">
                            <span class="font-semibold">{{ $message->from_staff ? __('Vitafolio support') : $ticket->name }}</span>
                            <span class="text-muted"> · <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->translatedFormat('j M Y, H:i') }}</time></span>
                        </h2>
                        <div class="prose mt-3 max-w-none prose-p:text-ink prose-li:text-ink prose-a:text-link prose-strong:text-ink dark:prose-invert" dir="auto">{!! $message->html() !!}</div>
                        @if ($message->attachments->isNotEmpty())
                            <ul class="mt-4 flex flex-wrap gap-3" aria-label="{{ __('Screenshots') }}">
                                @foreach ($message->attachments as $file)
                                    @php($src = route('support.attachment', array_filter(['attachment' => $file, 'token' => $token])))
                                    <li><a href="{{ $src }}" target="_blank" rel="noopener"><img src="{{ $src }}" alt="{{ __('Screenshot: :name', ['name' => $file->original_name]) }}" class="h-32 w-auto rounded border border-line object-cover" loading="lazy"></a></li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                </li>
            @endforeach
        </ol>

        @if ($ticket->status === 'closed')
            <p class="card mt-6 p-5 text-muted">{{ __('This ticket is closed. Open a new one if you still need help.') }}</p>
        @else
            <x-error-summary />
            <form method="POST" action="{{ route('support.reply', $ticket) }}" enctype="multipart/form-data" class="card mt-6 grid gap-4 p-6">
                @csrf
                @if ($token)<input type="hidden" name="token" value="{{ $token }}">@endif
                @include('support._compose', ['label' => __('Your reply')])
                @if ($staff)
                    <label class="flex items-center gap-2"><input type="checkbox" name="resolve" value="1" class="check"> {{ __('Mark as resolved') }}</label>
                @endif
                <div><button type="submit" class="btn btn-primary">{{ __('Send reply') }}</button></div>
            </form>
        @endif
    </div>
</x-layouts.app>
