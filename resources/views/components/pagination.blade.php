@if ($paginator->hasPages())
    <nav class="sort-panel" aria-label="{{ __('lavbe.pagination') }}">
        @if ($paginator->previousPageUrl())
            <a rel="prev" href="{{ $paginator->previousPageUrl() }}">{{ __('lavbe.previous') }}</a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))
                <span>{{ $element }}</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->nextPageUrl())
            <a rel="next" href="{{ $paginator->nextPageUrl() }}">{{ __('lavbe.next') }}</a>
        @endif
    </nav>
@endif
