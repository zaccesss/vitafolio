<x-auth-card title="Two-factor sign in" intro="Enter the six-digit code from your authenticator app.">
    <form method="POST" action="{{ route('two-factor.login') }}" class="grid gap-5">
        @csrf
        <x-field name="code" label="Code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus />
        <details>
            <summary class="cursor-pointer text-sm font-semibold">Lost your phone? Use a recovery code</summary>
            <div class="mt-4"><x-field name="recovery_code" label="Recovery code" autocomplete="one-time-code" /></div>
        </details>
        <button type="submit" class="btn btn-primary w-full">Sign in</button>
    </form>
</x-auth-card>
