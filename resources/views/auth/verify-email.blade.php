<x-auth-card :title="__('Confirm your email address')" :intro="__('We have sent a link to your email address. Select it to confirm your account.')">
    <p class="text-muted">{{ __('Until you confirm, you can edit your CVs but they will not appear in the directory. The link expires after an hour.') }}</p>
    <div class="mt-6 grid gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-full">{{ __('Send the link again') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary w-full">{{ __('Sign out') }}</button>
        </form>
    </div>
</x-auth-card>
