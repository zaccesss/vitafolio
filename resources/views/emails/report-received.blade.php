{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
A visitor reported a CV on {!! config('app.name') !!}.

CV: {!! $report->cv->title !!} by {!! $report->cv->user->name !!}
Address: {!! route('cv.show', $report->cv) !!}
Reason: {!! \App\Models\Report::REASONS[$report->reason] ?? $report->reason !!}
@if ($report->details)

What they said:
{!! $report->details !!}
@endif

Review it in moderation: {!! route('admin.index') !!}

--
Every admin receives this. The reporter stays anonymous.
