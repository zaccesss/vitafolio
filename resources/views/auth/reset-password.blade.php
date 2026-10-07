<x-auth-card :title="__('Choose a new password')">
    <form method="POST" action="{{ route('password.update') }}" class="grid gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-field name="email" :label="__('Email address')" type="email" :value="$request->email" required autocomplete="email" />
        <x-password-field name="password" :label="__('New password')" autocomplete="new-password"
            :hint="__('At least 10 characters with a letter and a number. Passwords found in known data breaches are refused.')" />
        <x-password-field name="password_confirmation" :label="__('Confirm new password')" autocomplete="new-password" />
        <button type="submit" class="btn btn-primary w-full">{{ __('Save new password') }}</button>
    </form>
</x-auth-card>
