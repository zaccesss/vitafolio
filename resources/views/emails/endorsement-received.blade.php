{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
{!! __(':name wrote an endorsement for your CV ":title" on :app.', ['name' => $endorsement->endorser->name, 'title' => $endorsement->cv->title, 'app' => config('app.name')]) !!}

{!! __('Relationship: :relationship', ['relationship' => __($endorsement->relationshipLabel())]) !!}
@if ($endorsement->context)
{!! __('Context: :context', ['context' => $endorsement->context]) !!}
@endif

{!! __('What they wrote:') !!}
{!! $endorsement->body !!}

{!! __('It stays hidden until you approve it. Approve, hide or delete it here: :url', ['url' => route('cvs.edit', [$endorsement->cv, 'endorsements'])]) !!}

--
{!! __('If it breaks the terms of use, you can report it from the same page.') !!}
