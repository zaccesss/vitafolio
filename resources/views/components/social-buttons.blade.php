@props(['intent' => 'Continue', 'divider' => true])
@php($providers = \App\Http\Controllers\SocialAuthController::enabled())
@if ($providers !== [])
    <div {{ $attributes->merge(['class' => 'grid gap-3'.($divider ? ' mt-6' : '')]) }}>
        @if ($divider)<p class="divider text-sm text-muted">or</p>@endif
        @foreach ($providers as $key => $provider)
            <a class="btn btn-secondary w-full" href="{{ route('social.redirect', $key) }}"><x-provider-icon :provider="$key" /><span>{{ $intent }} with {{ $provider['label'] }}</span></a>
        @endforeach
        <p class="text-center text-sm text-muted">By continuing you agree to the <a href="{{ route('terms') }}">terms</a> and <a href="{{ route('privacy') }}">privacy policy</a>.</p>
    </div>
@endif
