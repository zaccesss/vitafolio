@props(['intent' => 'continue', 'divider' => true])
@php($providers = \App\Http\Controllers\SocialAuthController::enabled())
@if ($providers !== [])
    <div {{ $attributes->merge(['class' => 'grid gap-3'.($divider ? ' mt-6' : '')]) }}>
        @if ($divider)<p class="divider text-sm text-muted">{{ __('or') }}</p>@endif
        @foreach ($providers as $key => $provider)
            <a class="btn btn-secondary w-full" href="{{ route('social.redirect', $key) }}"><x-provider-icon :provider="$key" /><span>{{ $intent === 'sign-up' ? __('Sign up with :provider', ['provider' => $provider['label']]) : __('Continue with :provider', ['provider' => $provider['label']]) }}</span></a>
        @endforeach
        <p class="text-center text-sm text-muted">{!! __('By continuing you agree to the :terms and :privacy.', ['terms' => '<a href="'.e(route('terms')).'">'.e(__('terms')).'</a>', 'privacy' => '<a href="'.e(route('privacy')).'">'.e(__('privacy policy')).'</a>']) !!}</p>
    </div>
@endif
