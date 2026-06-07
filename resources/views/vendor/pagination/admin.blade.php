@if ($paginator->hasPages())
    <div class="admin-pagination">
        <nav class="admin-pagination__nav" role="navigation" aria-label="صفحه‌بندی">
            <div class="admin-pagination__summary">
                @if ($paginator->total() > 0)
                    <p class="admin-pagination__count">
                        نمایش
                        <strong>{{ number_format($paginator->firstItem()) }}</strong>
                        تا
                        <strong>{{ number_format($paginator->lastItem()) }}</strong>
                        از
                        <strong>{{ number_format($paginator->total()) }}</strong>
                        مورد
                    </p>
                    <p class="admin-pagination__status">
                        صفحه
                        <strong>{{ number_format($paginator->currentPage()) }}</strong>
                        از
                        <strong>{{ number_format($paginator->lastPage()) }}</strong>
                    </p>
                @endif
            </div>

            <div class="admin-pagination__controls">
                @if ($paginator->onFirstPage())
                    <span class="admin-pagination__btn admin-pagination__btn--nav is-disabled" aria-disabled="true">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        <span>{{ __('pagination.previous') }}</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="admin-pagination__btn admin-pagination__btn--nav">
                        <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                        <span>{{ __('pagination.previous') }}</span>
                    </a>
                @endif

                <div class="admin-pagination__pages" role="list">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="admin-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="admin-pagination__page is-active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="admin-pagination__page" aria-label="{{ __('pagination.goto_page', ['page' => $page]) }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="admin-pagination__btn admin-pagination__btn--nav">
                        <span>{{ __('pagination.next') }}</span>
                        <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="admin-pagination__btn admin-pagination__btn--nav is-disabled" aria-disabled="true">
                        <span>{{ __('pagination.next') }}</span>
                        <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
                    </span>
                @endif
            </div>
        </nav>
    </div>
@endif
