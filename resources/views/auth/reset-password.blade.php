<x-auth-card title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="grid gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-field name="email" label="Email address" type="email" :value="$request->email" required autocomplete="email" />
        <x-password-field name="password" label="New password" autocomplete="new-password"
            hint="At least 10 characters with a letter and a number. Passwords found in known data breaches are refused." />
        <x-password-field name="password_confirmation" label="Confirm new password" autocomplete="new-password" />
        <button type="submit" class="btn btn-primary w-full">Save new password</button>
    </form>
</x-auth-card>
