@props(['cv', 'endorsement'])
{{-- the same reasons and moderation queue as a cv report; ids carry the endorsement so labels stay unique --}}
<form method="POST" action="{{ route('cv.endorsements.report', [$cv, $endorsement]) }}" class="mt-3 grid gap-4">
    @csrf
    <fieldset class="grid gap-2">
        <legend class="field-label">What is wrong?</legend>
        @foreach (\App\Models\Report::REASONS as $value => $label)
            <label class="flex items-start gap-2 text-sm"><input type="radio" name="reason" value="{{ $value }}" class="radio mt-0.5" required> {{ $label }}</label>
        @endforeach
    </fieldset>
    <x-field name="details" label="Details" type="textarea" rows="3" maxlength="1000" :field-id="'er'.$endorsement->id.'-details'" />
    <x-turnstile />
    <div><button type="submit" class="btn btn-sm btn-secondary">Send report</button></div>
</form>
