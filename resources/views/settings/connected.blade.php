<x-settings-page :title="__('Connected accounts')" :intro="__('Sign in with another site you already use.')">
    @php
        $providers = \App\Http\Controllers\SocialAuthController::enabled();
        $connected = $user->socialAccounts()->get()->keyBy('provider');
    @endphp
    <section id="connected" class="card p-6" aria-labelledby="connected-title">
        <h2 id="connected-title" class="text-xl">{{ __('Connected accounts') }}</h2>
        <p class="mt-2 text-muted">{{ __(':app only receives your name, email address and photo from a connected site. It never posts anything.', ['app' => config('app.name')]) }}</p>
        @if ($providers === [] && $connected->isEmpty())
            <p class="mt-4 text-sm text-muted">{{ __('No sign-in providers are set up on this copy of the site.') }}</p>
        @else
            <ul class="mt-4 divide-y divide-line rounded-xl border border-line">
                @foreach (config('vitafolio.social') as $key => $provider)
                    @continue(! isset($providers[$key]) && ! $connected->has($key))
                    @php($account = $connected->get($key))
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="flex items-center gap-2 font-semibold"><x-provider-icon :provider="$key" />{{ $provider['label'] }}</p>
                            <p class="text-sm text-muted">{{ $account ? ($account->email ? __('Connected as :email', ['email' => $account->email]) : __('Connected')) : __('Not connected') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($account)
                                @if ($account->avatar_url && $provider['photo_hosts'] !== [])
                                    <form method="POST" action="{{ route('social.photo', $key) }}" data-confirm="{{ __('Use your :provider photo as your profile photo? It replaces your current one.', ['provider' => $provider['label']]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary">{{ __('Use this photo') }}<span class="sr-only"> {{ __('from :provider', ['provider' => $provider['label']]) }}</span></button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('social.disconnect', $key) }}" data-confirm="{{ __('Disconnect :provider? You will no longer be able to sign in with it.', ['provider' => $provider['label']]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-secondary">{{ __('Disconnect') }}<span class="sr-only"> {{ $provider['label'] }}</span></button>
                                </form>
                            @else
                                <a class="btn btn-sm btn-secondary" href="{{ route('social.redirect', $key) }}">{{ __('Connect') }}<span class="sr-only"> {{ $provider['label'] }}</span></a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-settings-page>
