<x-auth-card :title="__('Two-factor sign in')" :intro="__('Enter the six-digit code from your authenticator app.')">
    <form method="POST" action="{{ route('two-factor.login') }}" class="grid gap-5">
        @csrf
        <x-field name="code" :label="__('Code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus />
        <details>
            <summary class="cursor-pointer text-sm font-semibold">{{ __('Lost your phone? Use a recovery code') }}</summary>
            <div class="mt-4"><x-field name="recovery_code" :label="__('Recovery code')" autocomplete="one-time-code" /></div>
        </details>
        <button type="submit" class="btn btn-primary w-full">{{ __('Sign in') }}</button>
    </form>
</x-auth-card>
