<x-settings-page :title="__('Signed-in devices')" :intro="__('Every browser signed in to your account. Sign out of any you do not recognise.')">
    <section class="card p-6" aria-labelledby="sessions-title">
        <h2 id="sessions-title" class="text-xl">{{ __('Where you are signed in') }}</h2>
        <ul class="mt-4 divide-y divide-line rounded-xl border border-line">
            @foreach ($sessions as $session)
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p class="font-semibold">{{ $session->device }} @if ($session->id === $current)<span class="badge ms-1">{{ __('This browser') }}</span>@endif</p>
                        <p class="text-sm text-muted">{{ $session->ip_address
                            ? __('Last active :time from :address', ['time' => $session->last_active->diffForHumans(), 'address' => $session->ip_address])
                            : __('Last active :time', ['time' => $session->last_active->diffForHumans()]) }}</p>
                    </div>
                    @if ($session->id !== $current)
                        <form method="POST" action="{{ route('account.sessions.end', $session->id) }}" data-confirm="{{ __('Sign out of :device?', ['device' => $session->device]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-secondary">{{ __('Sign out') }}<span class="sr-only"> {{ __('of :device', ['device' => $session->device]) }}</span></button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-sm text-muted">{{ __('Addresses are shown so you can tell a device apart; they are not kept once a session ends.') }}</p>
    </section>

    <section class="card p-6" aria-labelledby="others-title">
        <h2 id="others-title" class="text-xl">{{ __('Sign out everywhere else') }}</h2>
        <p class="mt-2 text-muted">{{ __('Signed in somewhere you no longer use, such as a shared computer? This signs out every device except this one.') }}</p>
        <x-error-summary bag="sessions" />
        <form method="POST" action="{{ route('account.sessions') }}" class="mt-4 grid gap-4 sm:max-w-md">
            @csrf
            <x-password-field name="current_password" :label="__('Your password')" error-bag="sessions" />
            <div><button type="submit" class="btn btn-secondary">{{ __('Sign out other devices') }}</button></div>
        </form>
    </section>
</x-settings-page>
