@php
    $twoFactorOn = $user->two_factor_confirmed_at !== null;
    $twoFactorPending = $user->two_factor_secret !== null && ! $twoFactorOn;
    // adding or removing a passkey needs a recent password check, like two-factor changes
    $confirmedAt = (int) session('auth.password_confirmed_at', 0);
    $canManagePasskeys = $confirmedAt > 0 && time() - $confirmedAt < (int) config('auth.password_timeout', 10800);
    $passkeys = $user->passkeys()->latest()->get(['id', 'name', 'created_at', 'last_used_at']);
@endphp
<x-layouts.app title="Account" noindex>
    <div class="container-page max-w-3xl py-8">
        <h1 class="text-3xl">Account</h1>
        <p class="mt-1 text-muted">Your details, sign-in security and data.</p>

        <section class="card mt-8 p-6" aria-labelledby="details-title">
            <h2 id="details-title" class="text-xl">Your details</h2>
            <x-error-summary bag="updateProfileInformation" />
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-4 grid gap-4">
                @csrf @method('PUT')
                <x-field name="name" label="Full name" :value="$user->name" required autocomplete="name" maxlength="100" error-bag="updateProfileInformation" />
                <x-field name="email" label="Email address" type="email" :value="$user->email" required autocomplete="email" maxlength="254" error-bag="updateProfileInformation"
                         hint="If you change it, you will need to confirm the new address before your CVs show in the directory again." />
                <div><button type="submit" class="btn btn-primary">Save details</button></div>
            </form>
        </section>

        <section class="card mt-8 p-6" aria-labelledby="password-title">
            <h2 id="password-title" class="text-xl">Password</h2>
            @if (! $user->has_password)
                <p class="mt-2 text-muted">You signed up with another site, so this account has no password yet. Set one to sign in with your email address as well.</p>
                <x-error-summary bag="setPassword" />
                <form method="POST" action="{{ route('account.password') }}" class="mt-4 grid gap-4">
                    @csrf @method('PUT')
                    <x-password-field name="password" label="New password" autocomplete="new-password" error-bag="setPassword"
                        hint="At least 10 characters with a letter and a number. Passwords found in known data breaches are refused." />
                    <x-password-field name="password_confirmation" label="Confirm new password" autocomplete="new-password" error-bag="setPassword" />
                    <div><button type="submit" class="btn btn-primary">Set password</button></div>
                </form>
            @else
            <x-error-summary bag="updatePassword" />
            <form method="POST" action="{{ route('user-password.update') }}" class="mt-4 grid gap-4">
                @csrf @method('PUT')
                <x-password-field name="current_password" label="Current password" error-bag="updatePassword" />
                <x-password-field name="password" label="New password" autocomplete="new-password" error-bag="updatePassword"
                    hint="At least 10 characters with a letter and a number. Passwords found in known data breaches are refused." />
                <x-password-field name="password_confirmation" label="Confirm new password" autocomplete="new-password" error-bag="updatePassword" />
                <div><button type="submit" class="btn btn-primary">Change password</button></div>
            </form>
            @endif
        </section>

        <section class="card mt-8 p-6" aria-labelledby="tfa-title">
            <h2 id="tfa-title" class="text-xl">Two-factor authentication</h2>
            <p class="mt-2 text-muted">Adds a second step at sign-in: a six-digit code from an authenticator app such as Google Authenticator, Microsoft Authenticator or 1Password.</p>
            <p class="mt-3"><span class="badge">{{ $twoFactorOn ? 'On' : ($twoFactorPending ? 'Waiting for confirmation' : 'Off') }}</span></p>

            @if ($twoFactorPending)
                <div class="mt-5 grid gap-5 sm:grid-cols-[auto_1fr] sm:items-start">
                    <div class="rounded-xl bg-white p-3" role="img" aria-label="QR code for your authenticator app">{!! $user->twoFactorQrCodeSvg() !!}</div>
                    <div>
                        <p>Scan the code with your authenticator app. You can also enter this key by hand:</p>
                        <p class="mt-2 font-mono text-sm break-all">{{ decrypt($user->two_factor_secret) }}</p>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-4 grid gap-3 sm:max-w-xs">
                            @csrf
                            <x-field name="code" label="Code from your app" inputmode="numeric" autocomplete="one-time-code" required maxlength="6" error-bag="confirmTwoFactorAuthentication" />
                            <div><button type="submit" class="btn btn-primary">Confirm and turn on</button></div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($twoFactorOn && session('status') === 'two-factor-authentication-confirmed' || $twoFactorOn && session('status') === 'recovery-codes-generated')
                <div class="mt-5 rounded-xl border border-line bg-raised p-4">
                    <h3 class="font-semibold">Recovery codes</h3>
                    <p class="mt-1 text-sm text-muted">Each code works once if you lose your phone. Store them in a password manager. They will not be shown again.</p>
                    <ul class="mt-3 grid gap-1 font-mono text-sm sm:grid-cols-2">
                        @foreach ($user->recoveryCodes() as $code)<li>{{ $code }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-5 flex flex-wrap gap-3">
                @if (! $twoFactorOn && ! $twoFactorPending)
                    <form method="POST" action="{{ route('two-factor.enable') }}">@csrf<button type="submit" class="btn btn-primary">Turn on</button></form>
                @endif
                @if ($twoFactorOn)
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">@csrf<button type="submit" class="btn btn-secondary">New recovery codes</button></form>
                @endif
                @if ($twoFactorOn || $twoFactorPending)
                    <form method="POST" action="{{ route('two-factor.disable') }}" data-confirm="Turn off two-factor authentication?">@csrf @method('DELETE')<button type="submit" class="btn btn-secondary">Turn off</button></form>
                @endif
            </div>
        </section>

        <section id="passkeys" class="card mt-8 p-6" aria-labelledby="passkeys-title">
            <h2 id="passkeys-title" class="text-xl">Passkeys</h2>
            <p class="mt-2 text-muted">Sign in with your fingerprint, face or device PIN instead of a password. A passkey stays on your device or password manager and cannot be phished.</p>
            @if ($passkeys->isNotEmpty())
                <ul class="mt-4 divide-y divide-line rounded-xl border border-line">
                    @foreach ($passkeys as $passkey)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <div>
                                <p class="font-semibold">{{ $passkey->name }}</p>
                                <p class="text-sm text-muted">Added {{ $passkey->created_at->format('j F Y') }}{{ $passkey->last_used_at ? ', last used '.$passkey->last_used_at->diffForHumans() : ', not used yet' }}</p>
                            </div>
                            @if ($canManagePasskeys)
                                <form method="POST" action="{{ route('passkey.destroy', $passkey->id) }}" data-confirm="Remove the passkey {{ $passkey->name }}? You can add it again later.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-secondary">Remove<span class="sr-only"> {{ $passkey->name }}</span></button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-muted">You have no passkeys yet.</p>
            @endif
            @if ($canManagePasskeys)
                <div x-data="passkeyRegister" data-done-url="{{ route('account.passkeys', ['added' => 1]) }}" class="mt-5">
                    <div class="grid gap-3 sm:max-w-md" :hidden="unsupported">
                        <label for="passkey-name" class="field-label">Name for the new passkey <span class="text-sm font-normal text-muted">(optional)</span></label>
                        <input id="passkey-name" type="text" class="input" maxlength="60" x-model="name" autocomplete="off">
                        <div><button type="button" class="btn btn-primary" x-on:click="add" :disabled="busy" x-text="buttonLabel">Add a passkey</button></div>
                        <p class="text-sm text-bad" role="alert" x-text="message"></p>
                    </div>
                    <p class="text-sm text-muted" hidden :hidden="supported">This browser does not support passkeys. Try a recent version of Chrome, Edge, Firefox or Safari.</p>
                </div>
            @else
                <a class="btn btn-secondary mt-5" href="{{ route('account.passkeys') }}">Confirm your password to add or remove passkeys</a>
            @endif
        </section>

        @php
            $providers = \App\Http\Controllers\SocialAuthController::enabled();
            $connected = $user->socialAccounts()->get()->keyBy('provider');
        @endphp
        @if ($providers !== [] || $connected->isNotEmpty())
            <section id="connected" class="card mt-8 p-6" aria-labelledby="connected-title">
                <h2 id="connected-title" class="text-xl">Connected accounts</h2>
                <p class="mt-2 text-muted">Sign in with another site you already use. {{ config('app.name') }} only receives your name, email address and photo. It never posts anything.</p>
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
            </section>
        @endif

        <section class="card mt-8 p-6" aria-labelledby="sessions-title">
            <h2 id="sessions-title" class="text-xl">Signed-in devices</h2>
            <p class="mt-2 text-muted">Signed in somewhere you no longer use, such as a shared computer? Sign out everywhere except here.</p>
            <x-error-summary bag="sessions" />
            <form method="POST" action="{{ route('account.sessions') }}" class="mt-4 grid gap-4 sm:max-w-md">
                @csrf
                <x-password-field name="current_password" label="Your password" error-bag="sessions" />
                <div><button type="submit" class="btn btn-secondary">Sign out other devices</button></div>
            </form>
        </section>

        <section class="card mt-8 p-6" aria-labelledby="data-title">
            <h2 id="data-title" class="text-xl">Your data</h2>
            <p class="mt-2 text-muted">Download everything stored about your account and CVs as a JSON file.</p>
            <a class="btn btn-secondary mt-4" href="{{ route('account.export') }}">Download my data</a>
        </section>

        <section class="card mt-8 border-bad p-6" aria-labelledby="delete-account-title">
            <h2 id="delete-account-title" class="text-xl text-bad">Delete your account</h2>
            <p class="mt-2 text-muted">Permanently deletes your account, every CV, uploaded file, picture and statistic. This cannot be undone. You will be asked for your password first.</p>
            <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 grid gap-4 sm:max-w-md">
                @csrf @method('DELETE')
                <x-field name="confirm" label="Type DELETE to confirm" required autocomplete="off" />
                <div><button type="submit" class="btn btn-danger">Delete my account</button></div>
            </form>
        </section>
    </div>
</x-layouts.app>
