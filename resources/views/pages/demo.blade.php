@php
    $demos = \App\Support\Demos::all();
    $demo = $demos[$clip];
    $others = array_diff_key($demos, [$clip => true]);
@endphp
<x-layouts.app :title="$demo['title']" :description="$demo['text']">
    <div class="container-page py-12">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm">
            <a href="{{ route('features') }}#demo">&larr; {{ __('Back to Features') }}</a>
        </nav>

        <h1 class="mt-4 text-3xl sm:text-4xl">{{ $demo['title'] }}</h1>
        <p class="mt-2 max-w-2xl text-lg text-muted">{{ $demo['text'] }}</p>

        {{-- one player per theme so the recording matches the page; the clips have no sound, so the
             captions are part of the picture and the description below covers the same steps --}}
        <div class="card mt-8 max-w-4xl overflow-hidden">
            @foreach (['' => 'dark:hidden', '-dark' => 'hidden dark:block'] as $suffix => $visibility)
                <video class="{{ $visibility }} aspect-[8/5] w-full bg-page" controls playsinline preload="metadata"
                    poster="{{ asset('demo/'.$clip.$suffix.'-still.webp') }}" aria-describedby="demo-description">
                    <source src="{{ asset('demo/'.$clip.$suffix.'.mp4') }}" type="video/mp4">
                </video>
            @endforeach
        </div>

        <section aria-labelledby="demo-description-title" class="mt-8 max-w-2xl">
            <h2 id="demo-description-title" class="text-xl">{{ __('What happens in this clip') }}</h2>
            <p id="demo-description" class="mt-2 text-muted">{{ $demo['alt'] }}</p>
        </section>

        <section aria-labelledby="more-title" class="mt-12">
            <h2 id="more-title" class="text-xl">{{ __('More clips') }}</h2>
            <ul class="mt-4 flex flex-wrap gap-3">
                @foreach ($others as $other => $item)
                    <li><a class="btn btn-secondary" href="{{ route('features.demo', $other) }}">{{ $item['title'] }}</a></li>
                @endforeach
                @guest
                    <li><a class="btn btn-primary" href="{{ route('register') }}">{{ __('Create your CV') }}</a></li>
                @endguest
            </ul>
        </section>
    </div>
</x-layouts.app>
