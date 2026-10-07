<x-auth-card :title="__('Create your account')" :intro="__('Free and quick to set up. You can keep several CVs and choose who sees each one.')" turnstile>
    <form method="POST" action="{{ route('register') }}" class="grid gap-5">
        @csrf
        <x-field name="name" :label="__('Full name')" required autocomplete="name" maxlength="100" autofocus />
        <x-field name="email" :label="__('Email address')" type="email" required autocomplete="email" maxlength="254"
                 :hint="__('We send a link to confirm it. It is never shown publicly unless you choose.')" />
        <x-password-field name="password" :label="__('Password')" autocomplete="new-password"
            :hint="__('At least 10 characters with a letter and a number. Passwords found in known data breaches are refused.')" />
        <x-password-field name="password_confirmation" :label="__('Confirm password')" autocomplete="new-password" />
        <label class="flex items-start gap-3">
            <input type="checkbox" name="terms" value="1" class="check mt-1" required @checked(old('terms')) @error('terms') aria-invalid="true" @enderror>
            <span>{!! __('I agree to the :terms and have read the :privacy.', ['terms' => '<a href="'.e(route('terms')).'">'.e(__('terms')).'</a>', 'privacy' => '<a href="'.e(route('privacy')).'">'.e(__('privacy policy')).'</a>']) !!}</span>
        </label>
        <x-turnstile />
        <button type="submit" class="btn btn-primary w-full">{{ __('Create account') }}</button>
    </form>
    <x-social-buttons intent="sign-up" />
    <x-slot:footer>{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></x-slot:footer>
</x-auth-card>
