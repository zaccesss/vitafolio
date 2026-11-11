@php
    $languages = \App\Support\Locales::ALL;
    $current = app()->getLocale();
@endphp
{{-- reached from the language menu by a visitor with no stored session. the form token is printed here,
     so the session starts on this page rather than on every page that shows the menu --}}
<x-layouts.app :title="__('Language')" noindex>
    <div class="container-page py-16">
        <div class="card mx-auto max-w-xl p-8">
            <h1 id="language-title" class="text-3xl">{{ __('Language') }}</h1>
            <form method="POST" action="{{ route('locale') }}" class="mt-6">
                @csrf
                <input type="hidden" name="from" value="{{ $from }}">
                <ul class="grid gap-3" aria-labelledby="language-title">
                    @foreach ($languages as $code => $language)
                        <li>
                            <button type="submit" name="locale" value="{{ $code }}" lang="{{ $language['html'] }}" dir="{{ $language['dir'] }}"
                                    class="btn w-full {{ $code === $chosen ? 'btn-primary' : 'btn-secondary' }}"
                                    @if ($code === $chosen) autofocus @endif
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
</x-layouts.app>
