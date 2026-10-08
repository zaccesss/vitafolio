{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
@if ($event === 'opened')
{!! __('Thank you for getting in touch. Your support request is open.') !!}

{!! __('Reference: :ref', ['ref' => $ticket->reference()]) !!}
{!! __('Subject: :subject', ['subject' => $ticket->subject]) !!}

{!! __('Read the conversation or add to it here:') !!}
{!! $link !!}

{!! __('Keep this email. The link is private and is how you reach this ticket without an account.') !!}
@elseif ($event === 'reply')
{!! __('There is a new reply on your support request :ref.', ['ref' => $ticket->reference()]) !!}

@if ($link)
{!! __('Read it and reply here:') !!}
{!! $link !!}
@else
{!! __('Open the link in the first email we sent you to read it and reply.') !!}
@endif
@else
{!! $event === 'new' ? __('A new support ticket was opened on :app.', ['app' => config('app.name')]) : __('There is a new reply on a support ticket on :app.', ['app' => config('app.name')]) !!}

{!! __('Reference: :ref', ['ref' => $ticket->reference()]) !!}
{!! __('Subject: :subject', ['subject' => $ticket->subject]) !!}
{!! __('From: :name <:email>', ['name' => $ticket->name, 'email' => $ticket->email]) !!}

{!! __('Open it: :url', ['url' => $link]) !!}
@endif

--
{!! __('Nobody who works on :app will ever ask for your password or a sign-in code.', ['app' => config('app.name')]) !!}
