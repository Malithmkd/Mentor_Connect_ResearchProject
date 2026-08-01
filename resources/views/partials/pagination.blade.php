@if ($paginator->hasPages())
    <div class="pagination-wrapper">
        {{-- Results Counter --}}
        @if ($paginator->firstItem())
            <div class="pagination__info">
                Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> results
            </div>
        @endif

        {{-- Nav controls --}}
        <nav class="pagination" aria-label="Pagination Navigation">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination__btn pagination__btn--disabled" aria-disabled="true" aria-label="Previous Page">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Prev</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination__btn" aria-label="Previous Page">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Prev</span>
                </a>
            @endif

            {{-- Page Number Links --}}
            <div class="pagination__pages">
                @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination__page pagination__page--active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination__page">{{ $page }}</a>
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination__btn" aria-label="Next Page">
                    <span>Next</span>
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @else
                <span class="pagination__btn pagination__btn--disabled" aria-disabled="true" aria-label="Next Page">
                    <span>Next</span>
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            @endif
        </nav>
    </div>
@endif
