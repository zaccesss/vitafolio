<x-settings-page :title="'Connected accounts'" :intro="'Sign in with another site you already use.'">
    @php
        $providers = \App\Http\Controllers\SocialAuthController::enabled();
        $connected = $user->socialAccounts()->get()->keyBy('provider');
    @endphp
    <section id="connected" class="card p-6" aria-labelledby="connected-title">
        <h2 id="connected-title" class="text-xl">Connected accounts</h2>
        <p class="mt-2 text-muted">{{ config('app.name') }} only receives your name, email address and photo from a connected site. It never posts anything.</p>
        @if ($providers === [] && $connected->isEmpty())
            <p class="mt-4 text-sm text-muted">No sign-in providers are set up on this copy of the site.</p>
        @else
            <ul class="mt-4 divide-y divide-line rounded-xl border border-line">
                @foreach (config('vitafolio.social') as $key => $provider)
                    @continue(! isset($providers[$key]) && ! $connected->has($key))
                    @php($account = $connected->get($key))
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $provider['label'] }}</p>
                            <p class="text-sm text-muted">{{ $account ? 'Connected'.($account->email ? ' as '.$account->email : '') : 'Not connected' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($account)
                                @if ($account->avatar_url && $provider['photo_hosts'] !== [])
                                    <form method="POST" action="{{ route('social.photo', $key) }}" data-confirm="Use your {{ $provider['label'] }} photo as your profile photo? It replaces your current one.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary">Use this photo<span class="sr-only"> from {{ $provider['label'] }}</span></button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('social.disconnect', $key) }}" data-confirm="Disconnect {{ $provider['label'] }}? You will no longer be able to sign in with it.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-secondary">Disconnect<span class="sr-only"> {{ $provider['label'] }}</span></button>
                                </form>
                            @else
                                <a class="btn btn-sm btn-secondary" href="{{ route('social.redirect', $key) }}">Connect<span class="sr-only"> {{ $provider['label'] }}</span></a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-settings-page>
