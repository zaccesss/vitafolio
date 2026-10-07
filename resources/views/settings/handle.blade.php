<x-settings-page :title="__('Handle')" :intro="__('Your username and the address of your profile page.')">
    <section class="card p-6" aria-labelledby="handle-title">
        <h2 id="handle-title" class="text-xl">{{ __('Handle') }}</h2>
        <p class="mt-2 text-muted">{!! __('Your profile lives at :address.', ['address' => '<strong dir="ltr">'.e(preg_replace('#^https?://#', '', route('profile.show', $user->handle))).'</strong>']) !!}</p>
        <x-error-summary bag="handle" />
        @if ($user->canChangeHandle())
            <form method="POST" action="{{ route('profile.handle') }}" class="mt-4 grid gap-3 sm:max-w-md">
                @csrf @method('PUT')
                <x-field name="handle" :label="__('New handle')" :value="$user->handle" required maxlength="30" autocomplete="off" error-bag="handle"
                         :hint="__('3 to 30 lowercase letters, numbers and hyphens. You can change it once every 30 days. Your old handle redirects here for 30 days.')" />
                <div><button type="submit" class="btn btn-secondary">{{ __('Change handle') }}</button></div>
            </form>
        @else
            <p class="mt-3"><span class="badge">{{ __('You can change it again on :date', ['date' => $user->nextHandleChange()->translatedFormat('j F Y')]) }}</span></p>
        @endif
    </section>
</x-settings-page>
