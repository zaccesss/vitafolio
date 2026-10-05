<x-auth-card title="Create your account" intro="Free and quick to set up. You can keep several CVs and choose who sees each one." turnstile>
    <form method="POST" action="{{ route('register') }}" class="grid gap-5">
        @csrf
        <x-field name="name" label="Full name" required autocomplete="name" maxlength="100" autofocus />
        <x-field name="email" label="Email address" type="email" required autocomplete="email" maxlength="254"
                 hint="We send a link to confirm it. It is never shown publicly unless you choose." />
        <x-password-field name="password" label="Password" autocomplete="new-password"
            hint="At least 10 characters with a letter and a number. Passwords found in known data breaches are refused." />
        <x-password-field name="password_confirmation" label="Confirm password" autocomplete="new-password" />
        <label class="flex items-start gap-3">
            <input type="checkbox" name="terms" value="1" class="check mt-1" required @checked(old('terms')) @error('terms') aria-invalid="true" @enderror>
            <span>I agree to the <a href="{{ route('terms') }}">terms</a> and have read the <a href="{{ route('privacy') }}">privacy policy</a>.</span>
        </label>
        <x-turnstile />
        <button type="submit" class="btn btn-primary w-full">Create account</button>
    </form>
    <x-social-buttons intent="Sign up" />
    <x-slot:footer>Already have an account? <a href="{{ route('login') }}">Sign in</a></x-slot:footer>
</x-auth-card>
