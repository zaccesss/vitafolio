<x-settings-page :title="'Name and email'" :intro="'Used to sign in and for messages about your account.'">
    <section class="card p-6" aria-labelledby="details-title">
        <h2 id="details-title" class="text-xl">Your details</h2>
        <x-error-summary bag="updateProfileInformation" />
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-4 grid gap-4">
            @csrf @method('PUT')
            <x-field name="name" label="Full name" :value="$user->name" required autocomplete="name" maxlength="100" error-bag="updateProfileInformation" />
            <x-field name="email" label="Email address" type="email" :value="$user->email" required autocomplete="email" maxlength="254" error-bag="updateProfileInformation"
                     hint="If you change it, you will need to confirm the new address before your CVs show in the directory again." />
            @if ($user->has_password)
                <x-password-field name="current_password" label="Your password" error-bag="updateProfileInformation" hint="Only needed when changing your email address." />
            @endif
            <div><button type="submit" class="btn btn-primary">Save details</button></div>
        </form>
    </section>
</x-settings-page>
