<x-layouts.app :title="__('Your support tickets')" noindex>
    <div class="container-page py-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-3xl">{{ __('Your support tickets') }}</h1>
            <a class="btn btn-primary" href="{{ route('support.create') }}">{{ __('Open a ticket') }}</a>
        </div>
        @if ($tickets->isEmpty())
            <p class="card mt-6 p-6 text-muted">{{ __('You have not opened any tickets.') }}</p>
        @else
            <ul class="mt-6 grid gap-3">
                @foreach ($tickets as $ticket)
                    <li>
                        <a href="{{ route('support.show', $ticket) }}" class="card card-hover flex flex-wrap items-center justify-between gap-3 p-4 no-underline">
                            <span>
                                <span class="text-sm text-muted">{{ $ticket->reference() }}</span>
                                <span class="block text-lg text-ink">{{ $ticket->subject }}</span>
                            </span>
                            <span class="flex items-center gap-3 text-sm">
                                <span class="badge">{{ \App\Models\SupportTicket::statusLabels()[$ticket->status] }}</span>
                                <span class="text-muted">{{ $ticket->last_activity_at->diffForHumans() }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-layouts.app>
