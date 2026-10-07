@props(['placement' => 'header'])
@php
    $current = app()->getLocale();
    $languages = \App\Support\Locales::ALL;
@endphp
{{-- each language is named in its own script and marked with its own lang, so a screen reader
     says it properly. plain buttons in a form: it works without javascript and from the keyboard --}}
@if ($placement === 'footer')
    <form method="POST" action="{{ route('locale') }}" class="flex flex-wrap items-center gap-x-3 gap-y-1">
        @csrf
        <h2 id="footer-language" class="text-sm font-semibold">{{ __('Language') }}</h2>
        <ul class="flex flex-wrap gap-x-3 gap-y-1" aria-labelledby="footer-language">
            @foreach ($languages as $code => $language)
                <li>
                    <button type="submit" name="locale" value="{{ $code }}" lang="{{ $language['html'] }}" dir="{{ $language['dir'] }}"
                            class="cursor-pointer underline-offset-4 hover:underline {{ $code === $current ? 'font-semibold text-ink' : 'text-link' }}"
                            @if ($code === $current) aria-current="true" @endif>{{ $language['name'] }}</button>
                </li>
            @endforeach
        </ul>
    </form>
@else
    <div class="relative" x-data="accountMenu" @keydown.escape.window="close" @click.outside="close">
        <button type="button" class="nav-link inline-flex items-center gap-2 py-1.5" aria-controls="language-menu" :aria-expanded="expanded" @click="toggle">
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/></svg>
            <span class="hidden lg:inline" lang="{{ $languages[$current]['html'] }}">{{ $languages[$current]['name'] }}</span>
            <span class="sr-only">{{ __('Language') }}</span>
        </button>
        <div id="language-menu" class="account-menu" :hidden="closed" hidden>
            <form method="POST" action="{{ route('locale') }}">
                @csrf
                <ul class="py-2">
                    @foreach ($languages as $code => $language)
                        <li>
                            <button type="submit" name="locale" value="{{ $code }}" lang="{{ $language['html'] }}" dir="{{ $language['dir'] }}"
                                    class="menu-link flex w-full cursor-pointer items-center justify-between gap-3 text-start"
                                    @if ($code === $current) aria-current="true" @endif>
                                <span>{{ $language['name'] }}</span>
                                @if ($code === $current)<span aria-hidden="true">&check;</span>@endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            </form>
        </div>
    </div>
@endif
