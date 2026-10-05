<x-auth-card title="Confirm your password" intro="This is a sensitive action, so please enter your password again.">
    <form method="POST" action="{{ route('password.confirm') }}" class="grid gap-5">
        @csrf
        <x-password-field name="password" label="Password" />
        <button type="submit" class="btn btn-primary w-full">Confirm</button>
    </form>
</x-auth-card>
