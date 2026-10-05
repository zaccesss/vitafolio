<x-auth-card title="Sign in" intro="Welcome back. Sign in to manage your CVs.">
    <form method="POST" action="{{ route('login') }}" class="grid gap-5">
        @csrf
        <x-field name="email" label="Email address" type="email" required autocomplete="username webauthn" autofocus />
        <x-password-field name="password" label="Password" />
        <label class="flex items-center gap-3"><input type="checkbox" name="remember" value="1" class="check"> Keep me signed in on this device</label>
        <button type="submit" class="btn btn-primary w-full">Sign in</button>
        <p class="text-center text-sm"><a href="{{ route('password.request') }}">Forgotten your password?</a></p>
    </form>
    {{-- shown only where the browser supports passkeys; without javascript the password form is all there is --}}
    @php($social = \App\Http\Controllers\SocialAuthController::enabled() !== [])
    @if ($social)<p class="divider mt-6 text-sm text-muted">or</p>@endif
    <div x-data="passkeyLogin" class="grid gap-3 {{ $social ? 'mt-3' : 'mt-6' }}" hidden :hidden="unsupported">
        @unless ($social)<p class="divider text-sm text-muted">or</p>@endunless
        <button type="button" class="btn btn-secondary w-full" x-on:click="signIn" :disabled="busy" x-text="buttonLabel">Sign in with a passkey</button>
        <p class="text-sm text-bad" role="alert" x-text="message"></p>
    </div>
    <x-social-buttons :divider="false" class="mt-3" />
    <x-slot:footer>New here? <a href="{{ route('register') }}">Create an account</a>, it is free.</x-slot:footer>
</x-auth-card>
