{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
{!! $headline !!}

{!! $detail !!}

When: {!! now()->timezone('Europe/London')->format('l j F Y, H:i') !!} (UK time)

If this was you, there is nothing to do.

If it was not you, your account may be at risk. Reset your password straight away:
{!! route('password.request') !!}

Then check your passkeys, two-factor setup and connected accounts under Settings, and sign out of other devices.

--
{!! config('app.name') !!}: {!! config('app.url') !!}
