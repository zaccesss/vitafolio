@php
    // fortify reports results as codes, so they are turned into plain sentences here
    $messages = [
        'profile-information-updated' => 'Your details have been updated.',
        'password-updated' => 'Your password has been changed. Other devices have been signed out.',
        'two-factor-authentication-enabled' => 'Scan the QR code below, then enter a code from your app to finish turning on two-factor authentication.',
        'two-factor-authentication-confirmed' => 'Two-factor authentication is on. Keep your recovery codes somewhere safe.',
        'two-factor-authentication-disabled' => 'Two-factor authentication is off.',
        'recovery-codes-generated' => 'New recovery codes created. The old ones no longer work.',
        'verification-link-sent' => 'A new verification link has been sent to your email address.',
        'other-sessions-ended' => 'You have been signed out of every other device.',
        'passkey-registered' => 'Passkey added. You can now sign in with it instead of your password.',
        'passkey-deleted' => 'Passkey removed.',
    ];
    $status = session('status');
    $status = $messages[$status] ?? $status;
@endphp
@if ($status)
    <div class="container-page pt-5" x-data="toast" x-show="shown">
        <div class="toast alert alert-success flex items-start justify-between gap-4" role="status">
            <p>{{ $status }}</p>
            <button type="button" class="btn btn-sm btn-ghost -my-1 shrink-0" @click="dismiss">Dismiss<span class="sr-only"> message</span></button>
        </div>
    </div>
@endif
@if (session('error'))
    <div class="container-page pt-5">
        <p class="alert alert-error" role="alert">{{ session('error') }}</p>
    </div>
@endif
