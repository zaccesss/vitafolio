<x-layouts.app title="Your profile" noindex>
    <div class="container-page max-w-3xl py-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl">Your profile</h1>
                <p class="mt-1 text-muted">Who you are, shared by all your CVs. Each CV can still use its own headline.</p>
            </div>
            <a class="btn btn-secondary" href="{{ route('profile.show', $user->handle) }}">View profile</a>
        </div>

        <section class="card mt-8 p-6" aria-labelledby="photo-title">
            <h2 id="photo-title" class="text-xl">Photo</h2>
            <x-error-summary bag="avatar" />
            <div class="mt-4 flex flex-wrap items-center gap-6">
                <x-avatar :user="$user" size="lg" />
                <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" class="grid flex-1 gap-3">
                    @csrf
                    <x-field name="avatar" label="Choose a photo" type="file" required accept="image/jpeg,image/png,image/gif,image/webp" error-bag="avatar"
                             :hint="'JPG, PNG, GIF or WebP, up to '.intdiv(config('vitafolio.limits.avatar_kb'), 1024).' MB. Location and camera data is removed.'" />
                    <div data-vue="AvatarCropper" data-props="{{ json_encode(['input' => 'f-avatar']) }}"></div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="btn btn-primary">Upload photo</button>
                    </div>
                </form>
            </div>
            @if ($user->hasAvatar())
                <form method="POST" action="{{ route('profile.avatar.delete') }}" class="mt-4" data-confirm="Remove your photo?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary">Remove photo</button>
                </form>
            @endif
        </section>

        <section class="card mt-8 p-6" aria-labelledby="handle-title">
            <h2 id="handle-title" class="text-xl">Handle</h2>
            <p class="mt-2 text-muted">Your profile lives at <strong>{{ preg_replace('#^https?://#', '', route('profile.show', $user->handle)) }}</strong>.</p>
            <x-error-summary bag="handle" />
            @if ($user->canChangeHandle())
                <form method="POST" action="{{ route('profile.handle') }}" class="mt-4 grid gap-3 sm:max-w-md">
                    @csrf @method('PUT')
                    <x-field name="handle" label="New handle" :value="$user->handle" required maxlength="30" autocomplete="off" error-bag="handle"
                             hint="3 to 30 lowercase letters, numbers and hyphens. You can change it once every 30 days. Your old handle redirects here for 30 days." />
                    <div><button type="submit" class="btn btn-secondary">Change handle</button></div>
                </form>
            @else
                <p class="mt-3"><span class="badge">You can change it again on {{ $user->nextHandleChange()->format('j F Y') }}</span></p>
            @endif
        </section>

        <form method="POST" action="{{ route('profile.update') }}" class="card mt-8 grid gap-5 p-6 sm:grid-cols-2">
            @csrf @method('PUT')
            <h2 class="text-xl sm:col-span-2">About you</h2>
            <div class="sm:col-span-2"><x-error-summary /></div>
            <x-field name="name" label="Full name" :value="$user->name" required autocomplete="name" maxlength="100" />
            <x-field name="pronouns" label="Pronouns" :value="$user->pronouns" maxlength="30" hint="For example: she/her" />
            <x-field class="sm:col-span-2" name="headline" label="Headline" :value="$user->headline" maxlength="120"
                     hint="One line, for example: Final year computer science student" />
            <x-field class="sm:col-span-2" name="bio" label="Short bio" type="textarea" rows="4" :value="$user->bio" maxlength="600" counter />
            <x-field name="location" label="Location" :value="$user->location" maxlength="100" autocomplete="address-level2" hint="For example: Birmingham, UK" />
            <x-field name="university" label="University or college" :value="$user->university" maxlength="100" hint="Lets people find you by university." />
            <x-field name="availability" label="Looking for" type="select" :value="$user->availability" :options="config('vitafolio.availability')" required />
            <x-field class="sm:col-span-2" name="links" label="Links" type="textarea" rows="3" :value="$user->links" maxlength="1500"
                     hint="One per line, up to 10. GitHub, LinkedIn, ORCID, Google Scholar, Mastodon, Bluesky, LeetCode and many more are named for you. Any other site shows its address." />

            <fieldset class="grid gap-3 sm:col-span-2">
                <legend class="field-label text-xl">Who can see your profile</legend>
                @foreach (config('vitafolio.profile_visibility') as $value => $label)
                    <label class="flex items-start gap-3 rounded-xl border-2 border-line p-3 has-[:checked]:border-brand has-[:checked]:bg-raised">
                        <input type="radio" name="profile_visibility" value="{{ $value }}" class="radio mt-0.5" @checked(old('profile_visibility', $user->profile_visibility) === $value)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </fieldset>

            <div class="sm:col-span-2"><button type="submit" class="btn btn-primary">Save profile</button></div>
        </form>
    </div>
</x-layouts.app>
