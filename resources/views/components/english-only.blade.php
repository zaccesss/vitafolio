{{-- legal and technical pages are kept in english alone; anyone reading in another language is told so --}}
@if (app()->getLocale() !== \App\Support\Locales::DEFAULT)
    <p {{ $attributes->merge(['class' => 'alert alert-info not-prose']) }}>{{ __('This page is only available in English. The English version is the one that applies.') }}</p>
@endif
