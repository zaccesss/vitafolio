<x-help-page slug="account-security">
    <h2>{{ __('Passwords') }}</h2>
    <p>{!! __('Passwords need at least 10 characters with a letter and a number. Passwords that have appeared in known data breaches are refused. Change yours under <strong>Settings</strong>, then <strong>Password and two-factor</strong>.') !!}</p>

    <h2>{{ __('Two-factor authentication') }}</h2>
    <p>{{ __('Turn on two-factor authentication to be asked for a code from an authenticator app each time you sign in. Keep the recovery codes somewhere safe, such as a password manager: each works once if you lose your phone.') }}</p>

    <h2>{{ __('Passkeys') }}</h2>
    <p>{!! __('A passkey lets you sign in with your fingerprint, face or device PIN instead of a password. It stays on your device or in your password manager and cannot be phished. Add one under <strong>Settings</strong>, then <strong>Passkeys</strong>.') !!}</p>

    <h2>{{ __('Connected accounts') }}</h2>
    <p>{{ __('Connect Google, GitHub or Microsoft to sign in with them. Vitafolio only receives your name, email address and photo. It never posts anything. Your account always keeps at least one way to sign in, so the last one cannot be disconnected.') }}</p>

    <h2>{{ __('Signed-in devices') }}</h2>
    <p>{!! __('Signed in on a shared computer? Sign out of every other device under <strong>Settings</strong>, then <strong>Signed-in devices</strong>.') !!}</p>
</x-help-page>
