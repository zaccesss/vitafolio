@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pages">
        <p class="mb-3 text-center text-sm text-muted">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>
        <ul class="flex flex-wrap items-center justify-center gap-2">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn btn-sm btn-secondary opacity-50" aria-disabled="true">Previous</span>
                @else
                    <a class="btn btn-sm btn-secondary" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous<span class="sr-only"> page</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-muted">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="btn btn-sm btn-primary" aria-current="page"><span class="sr-only">Page </span>{{ $page }}</span>
                            @else
                                <a class="btn btn-sm btn-secondary" href="{{ $url }}"><span class="sr-only">Page </span>{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a class="btn btn-sm btn-secondary" href="{{ $paginator->nextPageUrl() }}" rel="next">Next<span class="sr-only"> page</span></a>
                @else
                    <span class="btn btn-sm btn-secondary opacity-50" aria-disabled="true">Next</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
