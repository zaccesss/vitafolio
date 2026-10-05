<x-settings-page :title="'Handle'" :intro="'Your username and the address of your profile page.'">
    <section class="card p-6" aria-labelledby="handle-title">
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
</x-settings-page>
