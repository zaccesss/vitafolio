<x-layouts.app :title="'LaTeX: '.$cv->title" noindex>
    <div class="container-page max-w-none py-8 xl:px-10">
        <nav aria-label="Breadcrumb" class="text-sm text-muted">
            <ol class="flex flex-wrap gap-2">
                <li><a href="{{ route('dashboard') }}">My CVs</a> <span aria-hidden="true">/</span></li>
                <li><a href="{{ route('cvs.edit', [$cv, 'file']) }}">{{ $cv->title }}</a> <span aria-hidden="true">/</span></li>
                <li aria-current="page">LaTeX</li>
            </ol>
        </nav>
        <h1 class="mt-3 text-3xl">LaTeX editor</h1>
        <p class="mt-1 max-w-3xl text-muted">
            Write your CV in LaTeX and compile it to a PDF right here. Compiling happens in your browser, so your work stays on your device until you save.
            The first compile downloads the LaTeX engine (around 120 MB, then cached). Saving stores your source and attaches the latest compiled PDF to this CV.
            <a href="{{ route('help.topic', 'files-and-latex') }}" target="_blank" rel="noopener">LaTeX help<x-new-tab /></a>
        </p>

        <div class="mt-6" data-vue="LatexStudio" data-props="{{ json_encode([
            'saveUrl' => route('cvs.latex.update', $cv),
            'csrf' => csrf_token(),
            'initialSource' => $source,
            'starters' => $starters,
            'templates' => $templates,
            'assetsUrl' => (string) $assetsUrl,
            'viewUrl' => route('cv.show', $cv),
            'nonce' => \Illuminate\Support\Facades\Vite::cspNonce() ?? '',
        ]) }}">
            {{-- without javascript the source can still be read and copied --}}
            <div class="card p-5">
                <p class="text-muted">The LaTeX editor needs JavaScript. Your current source is below.</p>
                <pre class="mt-3 max-h-[60vh] overflow-auto font-mono text-sm whitespace-pre-wrap">{{ $source }}</pre>
            </div>
        </div>
    </div>
</x-layouts.app>
