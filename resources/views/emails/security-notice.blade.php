{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
{!! __($headline, $replace) !!}

{!! __($detail, $replace) !!}

{!! __('When: :time (UK time)', ['time' => now()->timezone('Europe/London')->translatedFormat('l j F Y, H:i')]) !!}

{!! __('If this was you, there is nothing to do.') !!}

{!! __('If it was not you, your account may be at risk. Reset your password straight away:') !!}
{!! route('password.request') !!}

{!! __('Then check your passkeys, two-factor setup and connected accounts under Settings, and sign out of other devices.') !!}

--
{!! config('app.name') !!}: {!! config('app.url') !!}
