<x-settings-page :title="'Your data'" :intro="'Download everything you have stored here or delete your account.'">
    <section class="card p-6" aria-labelledby="data-title">
        <h2 id="data-title" class="text-xl">Your data</h2>
        <p class="mt-2 text-muted">Download everything stored about your account and CVs as a JSON file.</p>
        <a class="btn btn-secondary mt-4" href="{{ route('account.export') }}">Download my data</a>
    </section>

    <section class="card border-bad p-6" aria-labelledby="delete-account-title">
        <h2 id="delete-account-title" class="text-xl text-bad">Delete your account</h2>
        <p class="mt-2 text-muted">Permanently deletes your account, every CV, uploaded file, picture and statistic. This cannot be undone. You will be asked for your password first.</p>
        <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 grid gap-4 sm:max-w-md">
            @csrf @method('DELETE')
            <x-field name="confirm" label="Type DELETE to confirm" required autocomplete="off" />
            <div><button type="submit" class="btn btn-danger">Delete my account</button></div>
        </form>
    </section>
</x-settings-page>
