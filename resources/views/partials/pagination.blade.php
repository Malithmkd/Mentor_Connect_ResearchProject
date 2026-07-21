@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="pagination__btn pagination__btn--disabled" aria-disabled="true">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Prev
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination__btn">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Prev
            </a>
        @endif

        {{-- Numbers --}}
        <div class="pagination__pages">
            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="pagination__page pagination__page--active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="pagination__page">{{ $page }}</a>
                @endif
            @endforeach
        </div>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination__btn">
                Next
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        @else
            <span class="pagination__btn pagination__btn--disabled" aria-disabled="true">
                Next
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        @endif
    </nav>
@endif
