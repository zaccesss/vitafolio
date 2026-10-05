<x-settings-page :title="'Signed-in devices'" :intro="'Every browser signed in to your account. Sign out of any you do not recognise.'">
    <section class="card p-6" aria-labelledby="sessions-title">
        <h2 id="sessions-title" class="text-xl">Where you are signed in</h2>
        <ul class="mt-4 divide-y divide-line rounded-xl border border-line">
            @foreach ($sessions as $session)
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p class="font-semibold">{{ $session->device }} @if ($session->id === $current)<span class="badge ml-1">This browser</span>@endif</p>
                        <p class="text-sm text-muted">Last active {{ $session->last_active->diffForHumans() }}@if ($session->ip_address) from {{ $session->ip_address }}@endif</p>
                    </div>
                    @if ($session->id !== $current)
                        <form method="POST" action="{{ route('account.sessions.end', $session->id) }}" data-confirm="Sign out of {{ $session->device }}?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-secondary">Sign out<span class="sr-only"> of {{ $session->device }}</span></button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-sm text-muted">Addresses are shown so you can tell a device apart; they are not kept once a session ends.</p>
    </section>

    <section class="card p-6" aria-labelledby="others-title">
        <h2 id="others-title" class="text-xl">Sign out everywhere else</h2>
        <p class="mt-2 text-muted">Signed in somewhere you no longer use, such as a shared computer? This signs out every device except this one.</p>
        <x-error-summary bag="sessions" />
        <form method="POST" action="{{ route('account.sessions') }}" class="mt-4 grid gap-4 sm:max-w-md">
            @csrf
            <x-password-field name="current_password" label="Your password" error-bag="sessions" />
            <div><button type="submit" class="btn btn-secondary">Sign out other devices</button></div>
        </form>
    </section>
</x-settings-page>
