<x-auth-card :title="__('Confirm your password')" :intro="__('This is a sensitive action, so please enter your password again.')">
    <form method="POST" action="{{ route('password.confirm') }}" class="grid gap-5">
        @csrf
        <x-password-field name="password" :label="__('Password')" />
        <button type="submit" class="btn btn-primary w-full">{{ __('Confirm') }}</button>
    </form>
</x-auth-card>
