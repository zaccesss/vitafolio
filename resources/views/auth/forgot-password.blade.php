<x-auth-card :title="__('Reset your password')" :intro="__('Enter your email address and we will send you a link to choose a new password.')">
    @if (session('status'))
        <p class="alert alert-success mb-5" role="status">{{ __('If an account uses that address, a reset link is on its way. Check your inbox and spam folder.') }}</p>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="grid gap-5">
        @csrf
        <x-field name="email" :label="__('Email address')" type="email" required autocomplete="email" autofocus />
        <button type="submit" class="btn btn-primary w-full">{{ __('Send reset link') }}</button>
    </form>
    <x-slot:footer><a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></x-slot:footer>
</x-auth-card>
