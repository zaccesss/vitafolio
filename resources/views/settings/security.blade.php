@php
    $twoFactorOn = $user->two_factor_confirmed_at !== null;
    $twoFactorPending = $user->two_factor_secret !== null && ! $twoFactorOn;
    // adding or removing a passkey needs a recent password check, like two-factor changes
    $confirmedAt = (int) session('auth.password_confirmed_at', 0);
    $canManagePasskeys = $confirmedAt > 0 && time() - $confirmedAt < (int) config('auth.password_timeout', 10800);
    $passkeys = $user->passkeys()->latest()->get(['id', 'name', 'created_at', 'last_used_at']);
@endphp
<x-settings-page :title="'Password and two-factor'" :intro="'Keep your account safe with a strong password and a second step at sign-in.'">
    <section class="card p-6" aria-labelledby="password-title">
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

    <section class="card p-6" aria-labelledby="tfa-title">
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
</x-settings-page>
