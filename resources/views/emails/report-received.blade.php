{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
{!! $report->endorsement_id ? __('A visitor reported an endorsement on a CV on :app.', ['app' => config('app.name')]) : __('A visitor reported a CV on :app.', ['app' => config('app.name')]) !!}

{!! __('CV: :title by :name', ['title' => $report->cv->title, 'name' => $report->cv->user->name]) !!}
{!! __('Address: :url', ['url' => route('cv.show', $report->cv)]) !!}
@if ($report->endorsement)
{!! __('Endorsement by: :name', ['name' => $report->endorsement->endorser->name]) !!}
{!! __('Endorsement text:') !!}
{!! $report->endorsement->body !!}

@endif
{!! __('Reason: :reason', ['reason' => __(\App\Models\Report::REASONS[$report->reason] ?? $report->reason)]) !!}
@if ($report->details)

{!! __('What they said:') !!}
{!! $report->details !!}
@endif

{!! __('Review it in moderation: :url', ['url' => route('admin.index')]) !!}

--
{!! __('Every admin receives this. The reporter stays anonymous.') !!}
