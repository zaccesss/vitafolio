@props(['user'])
@php
    $used = $user->storageUsed();
    $allowance = $user->storageAllowance();
    $mb = fn (int $bytes) => number_format($bytes / 1048576, $bytes < 10485760 ? 1 : 0);
@endphp
{{-- the numbers are always written out, so the bar is never the only way to read the amount --}}
<div {{ $attributes->merge(['class' => 'grid gap-1.5']) }}>
    <label for="storage-used" class="text-sm font-semibold">{{ __('Storage used: :used MB of :allowance MB', ['used' => $mb($used), 'allowance' => $mb($allowance)]) }}</label>
    <progress id="storage-used" class="storage-meter" max="{{ $allowance }}" value="{{ min($used, $allowance) }}">{{ __(':size MB', ['size' => $mb($used)]) }}</progress>
    <p class="text-sm text-muted">{{ __('CV files and project images and videos count towards it. Profile photos and your text do not.') }}</p>
</div>
