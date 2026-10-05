@php
    $twoFactorOn = $user->two_factor_confirmed_at !== null;
    $twoFactorPending = $user->two_factor_secret !== null && ! $twoFactorOn;
    // adding or removing a passkey needs a recent password check, like two-factor changes
    $confirmedAt = (int) session('auth.password_confirmed_at', 0);
    $canManagePasskeys = $confirmedAt > 0 && time() - $confirmedAt < (int) config('auth.password_timeout', 10800);
    $passkeys = $user->passkeys()->latest()->get(['id', 'name', 'created_at', 'last_used_at']);
@endphp
<x-settings-page :title="'Passkeys'" :intro="'Sign in with your fingerprint, face or device PIN.'">
    <section id="passkeys" class="card p-6" aria-labelledby="passkeys-title">
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
</x-settings-page>
