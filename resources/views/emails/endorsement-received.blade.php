{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
{!! $endorsement->endorser->name !!} wrote an endorsement for your CV "{!! $endorsement->cv->title !!}" on {!! config('app.name') !!}.

Relationship: {!! $endorsement->relationshipLabel() !!}
@if ($endorsement->context)
Context: {!! $endorsement->context !!}
@endif

What they wrote:
{!! $endorsement->body !!}

It stays hidden until you approve it. Approve, hide or delete it here: {!! route('cvs.edit', [$endorsement->cv, 'endorsements']) !!}

--
If it breaks the terms of use, you can report it from the same page.
