@if ($paginator->hasPages())
    <nav class="fe-courses-pagination" aria-label="Course pages">
        <ul class="pagination justify-content-center flex-wrap gap-1 mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->previousPageUrl() ?? '#' }}" rel="prev" aria-label="Previous page">Previous</a>
            </li>

            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}" aria-label="Page {{ $page }}" {{ $page == $paginator->currentPage() ? 'aria-current=page' : '' }}>{{ $page }}</a>
                </li>
            @endforeach

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->nextPageUrl() ?? '#' }}" rel="next" aria-label="Next page">Next</a>
            </li>
        </ul>
    </nav>
@endif
