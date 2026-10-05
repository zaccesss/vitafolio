<x-auth-card title="Confirm your email address" intro="We have sent a link to your email address. Select it to confirm your account.">
    <p class="text-muted">Until you confirm, you can edit your CVs but they will not appear in the directory. The link expires after an hour.</p>
    <div class="mt-6 grid gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-full">Send the link again</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary w-full">Sign out</button>
        </form>
    </div>
</x-auth-card>
