@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="page muted">{{ __('pagination.previous') }}</span>
        @else
            <a class="page" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a>
        @endif

        <span class="page" aria-current="page">
            {{ __('pagination.page_of', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="page" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a>
        @else
            <span class="page muted">{{ __('pagination.next') }}</span>
        @endif
    </nav>
@endif
