@props(['intent' => 'Continue', 'divider' => true])
@php($providers = \App\Http\Controllers\SocialAuthController::enabled())
{{-- plain text buttons: provider logos come with brand rules; the name alone is clear --}}
@if ($providers !== [])
    <div {{ $attributes->merge(['class' => 'grid gap-3'.($divider ? ' mt-6' : '')]) }}>
        @if ($divider)<p class="divider text-sm text-muted">or</p>@endif
        @foreach ($providers as $key => $provider)
            <a class="btn btn-secondary w-full" href="{{ route('social.redirect', $key) }}">{{ $intent }} with {{ $provider['label'] }}</a>
        @endforeach
        <p class="text-center text-sm text-muted">By continuing you agree to the <a href="{{ route('terms') }}">terms</a> and <a href="{{ route('privacy') }}">privacy policy</a>.</p>
    </div>
@endif
