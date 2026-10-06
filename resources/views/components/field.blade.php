@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'rows' => 5,
    'options' => [],
    'counter' => false,
    'errorBag' => 'default',
    'fieldId' => null,
])
@php
    // repeated forms on one page pass their own id so labels never point at the wrong field
    $id = $fieldId ?? 'f-'.str_replace(['[', ']', '.'], '-', $name);
    $error = $errors->getBag($errorBag)->first($name);
    $current = old($name, $value);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error ' : '').($counter ? $id.'-count' : ''));
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'field']) }} @if($counter) x-data="counter" @endif>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}@if ($required)<span class="ml-0.5 text-bad" aria-hidden="true">*</span><span class="sr-only"> (required)</span>@else <span class="text-sm font-normal text-muted">(optional)</span>@endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="input"
            @required($required) @if($error) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class') }}>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="input"
            @required($required) @if($error) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class') }}>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" class="input"
            @if($type !== 'password' && $type !== 'file') value="{{ $current }}" @endif
            @required($required) @if($error) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class') }}>
    @endif

    {{-- hints sit under the box, so boxes side by side line up whether or not each has a hint --}}
    @if ($hint)<p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>@endif
    @if ($counter)<p id="{{ $id }}-count" class="mt-1 text-sm text-muted" aria-live="polite" x-text="text"></p>@endif
    @if ($error)<p id="{{ $id }}-error" class="field-error"><span class="sr-only">Error: </span>{{ $error }}</p>@endif
</div>
