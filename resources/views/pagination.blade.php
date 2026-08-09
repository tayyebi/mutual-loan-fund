@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="page muted">Previous</span>
        @else
            <a class="page" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        <span class="page" aria-current="page">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="page" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="page muted">Next</span>
        @endif
    </nav>
@endif
