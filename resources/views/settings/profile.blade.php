<x-settings-page :title="__('Public profile')" :intro="__('What appears on your profile page and on every CV.')">
    <form method="POST" action="{{ route('profile.update') }}" class="card grid gap-5 p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <h2 class="text-xl sm:col-span-2">{{ __('About you') }}</h2>
        <div class="sm:col-span-2"><x-error-summary /></div>
        <x-field name="name" :label="__('Full name')" :value="$user->name" required autocomplete="name" maxlength="100" />
        <x-field name="pronouns" :label="__('Pronouns')" :value="$user->pronouns" maxlength="30" :hint="__('For example: she/her')" />
        <x-field class="sm:col-span-2" name="headline" :label="__('Headline')" :value="$user->headline" maxlength="120"
                 :hint="__('One line, for example: Final year computer science student')" />
        <x-field class="sm:col-span-2" name="bio" :label="__('Short bio')" type="textarea" rows="4" :value="$user->bio" maxlength="600" counter />
        <x-field name="location" :label="__('Location')" :value="$user->location" maxlength="100" autocomplete="address-level2" :hint="__('For example: Birmingham, UK')" />
        <x-field name="university" :label="__('University or college')" :value="$user->university" maxlength="100" :hint="__('Lets people find you by university.')" />
        <x-field name="availability" :label="__('Looking for')" type="select" :value="$user->availability" :options="array_map('__', config('vitafolio.availability'))" required />
        <x-field class="sm:col-span-2" name="links" :label="__('Links')" type="textarea" rows="3" :value="$user->links" maxlength="1500"
                 :hint="__('One per line, up to 10. GitHub, LinkedIn, ORCID, Google Scholar, Mastodon, Bluesky, LeetCode and many more are named for you. Any other site shows its address.')" />

        <fieldset class="grid gap-3 sm:col-span-2">
            <legend class="field-label text-xl">{{ __('Who can see your profile') }}</legend>
            @foreach (config('vitafolio.profile_visibility') as $value => $label)
                <label class="flex items-start gap-3 rounded-xl border-2 border-line p-3 has-[:checked]:border-brand has-[:checked]:bg-raised">
                    <input type="radio" name="profile_visibility" value="{{ $value }}" class="radio mt-0.5" @checked(old('profile_visibility', $user->profile_visibility) === $value)>
                    <span>{{ __($label) }}</span>
                </label>
            @endforeach
        </fieldset>

        <div class="sm:col-span-2"><button type="submit" class="btn btn-primary">{{ __('Save profile') }}</button></div>
    </form>
</x-settings-page>
