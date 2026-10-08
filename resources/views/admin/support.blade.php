@php($statuses = \App\Models\SupportTicket::statusLabels())
<x-layouts.app :title="__('Support queue')" noindex>
    <div class="container-page py-8">
        <h1 class="text-3xl">{{ __('Support queue') }}</h1>
        <nav class="mt-6 flex gap-1 overflow-x-auto border-b-2 border-line" aria-label="{{ __('Ticket status') }}">
            @foreach ($statuses as $value => $label)
                <a class="tab-link" href="{{ route('admin.support', array_filter(['status' => $value, 'category' => $category])) }}" @if ($status === $value) aria-current="page" @endif>{{ $label }} <span class="ms-1 text-sm font-normal text-muted">{{ $counts[$value] ?? 0 }}</span></a>
            @endforeach
        </nav>
        <form method="GET" class="mt-4 flex flex-wrap items-end gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <label for="cat" class="field-label">{{ __('Category') }}</label>
            <select id="cat" name="category" class="input w-auto">
                <option value="">{{ __('All categories') }}</option>
                @foreach (\App\Models\SupportTicket::categoryLabels() as $value => $label)<option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>@endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
        </form>
        @if ($tickets->isEmpty())
            <p class="card mt-6 p-6 text-muted">{{ __('No tickets here.') }}</p>
        @else
            <ul class="mt-6 grid gap-3">
                @foreach ($tickets as $ticket)
                    <li>
                        <a href="{{ route('support.show', $ticket) }}" class="card card-hover grid gap-1 p-4 no-underline sm:grid-cols-[1fr_auto]">
                            <span>
                                <span class="text-sm text-muted">{{ $ticket->reference() }} · {{ \App\Models\SupportTicket::categoryLabels()[$ticket->category] ?? $ticket->category }} · {{ $ticket->name }}</span>
                                <span class="block text-lg text-ink">{{ $ticket->subject }}</span>
                            </span>
                            <span class="text-sm text-muted">{{ __('Messages: :count', ['count' => $ticket->messages_count]) }} · {{ $ticket->last_activity_at->diffForHumans() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-layouts.app>
