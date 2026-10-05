@props(['bag' => 'default', 'prefix' => 'f-'])
@php($bagErrors = $errors->getBag($bag))
@if ($bagErrors->any())
    <div class="alert alert-error mb-6" role="alert" tabindex="-1" data-focus-first>
        <h2 class="text-base font-semibold text-bad">There {{ $bagErrors->count() === 1 ? 'is a problem' : 'are '.$bagErrors->count().' problems' }} to fix</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($bagErrors->messages() as $field => $messages)
                <li><a class="text-bad" href="#{{ $prefix }}{{ str_replace(['[', ']', '.'], '-', $field) }}">{{ $messages[0] }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
