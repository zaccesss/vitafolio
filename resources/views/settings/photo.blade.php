<x-settings-page :title="'Photo'" :intro="'Shown on your profile, your CVs and their share images.'">
    <section class="card p-6" aria-labelledby="photo-title">
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
</x-settings-page>
