@if (\App\Rules\Turnstile::enabled())
    <div class="field">
        <div class="cf-turnstile min-h-[65px]" data-sitekey="{{ config('vitafolio.turnstile.site_key') }}" data-theme="auto"></div>
        @error('cf-turnstile-response')<p class="field-error">{{ $message }}</p>@enderror
    </div>
@endif
