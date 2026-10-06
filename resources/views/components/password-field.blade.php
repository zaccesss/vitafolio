@props(['name' => 'password', 'label' => 'Password', 'hint' => null, 'autocomplete' => 'current-password', 'required' => true, 'errorBag' => null])
@php
    $id = 'f-'.$name;
    // fortify uses a named bag on the account page; the default bag is used everywhere else
    $error = $errorBag ? $errors->getBag($errorBag)->first($name) : collect($errors->getBags())->map->first($name)->filter()->first();
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp
<div class="field" x-data="password">
    <label for="{{ $id }}" class="field-label">{{ $label }}@if ($required)<span class="ml-0.5 text-bad" aria-hidden="true">*</span><span class="sr-only"> (required)</span>@else <span class="text-sm font-normal text-muted">(optional)</span>@endif</label>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="password" :type="type" class="input pr-24" autocomplete="{{ $autocomplete }}"
            @required($required) @if($error) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        {{-- shown only once alpine runs, since it cannot work without it --}}
        <button type="button" x-cloak @click="toggle" :aria-pressed="pressed"
            class="btn btn-sm btn-secondary absolute top-1/2 right-1.5 -translate-y-1/2" x-text="label" aria-controls="{{ $id }}">Show</button>
    </div>
    @if ($hint)<p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>@endif
    @if ($error)<p id="{{ $id }}-error" class="field-error"><span class="sr-only">Error: </span>{{ $error }}</p>@endif
</div>
