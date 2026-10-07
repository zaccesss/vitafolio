@props(['rows', 'title', 'id', 'empty' => null])
@php $max = max(1, ...array_values($rows ?: [0])); @endphp
<figure {{ $attributes->merge(['class' => 'card p-5']) }} aria-labelledby="{{ $id }}-title">
    <figcaption id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</figcaption>
    @if ($rows === [])
        <p class="mt-3 text-sm text-muted">{{ $empty ?? __('Nothing to show yet.') }}</p>
    @else
        {{-- a list rather than a picture: each row reads as its label and number --}}
        <ul class="mt-4 grid gap-3">
            @foreach ($rows as $label => $value)
                <li>
                    <div class="flex justify-between gap-3 text-sm"><span class="truncate">{{ $label }}</span><span class="font-semibold">{{ $value }}</span></div>
                    <svg viewBox="0 0 100 6" preserveAspectRatio="none" class="mt-1 h-2 w-full" aria-hidden="true">
                        <rect width="100" height="6" rx="3" class="fill-raised" />
                        <rect width="{{ max(2, round(100 * $value / $max, 1)) }}" height="6" rx="3" class="fill-brand" />
                    </svg>
                </li>
            @endforeach
        </ul>
    @endif
</figure>
