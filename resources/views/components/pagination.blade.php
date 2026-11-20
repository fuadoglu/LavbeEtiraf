@props(['paginator', 'method' => 'get', 'action' => null, 'fields' => [], 'fragment' => null])

@if($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $current - 1, $current, $current + 1, $last])
            ->filter(fn ($page) => $page >= 1 && $page <= $last)
            ->unique()->sort()->values();
    @endphp
    <nav class="pagination" aria-label="{{ __('lavbe.pagination') }}">
        <div class="pagination-pages">
            @if($paginator->onFirstPage())
                <span class="pagination-control is-disabled" aria-disabled="true">{{ __('lavbe.previous') }}</span>
            @elseif($method === 'post')
                <form method="post" action="{{ $action }}">@csrf
                    @foreach($fields as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                    <input type="hidden" name="{{ $paginator->getPageName() }}" value="{{ $current - 1 }}">
                    <button class="pagination-control" type="submit">{{ __('lavbe.previous') }}</button>
                </form>
            @else
                <a class="pagination-control" href="{{ $paginator->previousPageUrl() }}{{ $fragment ? '#'.$fragment : '' }}">{{ __('lavbe.previous') }}</a>
            @endif

            @php($previous = null)
            @foreach($pages as $page)
                @if($previous !== null && $page > $previous + 1)<span class="pagination-ellipsis" aria-hidden="true">…</span>@endif
                @if($page === $current)
                    <span class="pagination-number is-current" aria-current="page">{{ $page }}</span>
                @elseif($method === 'post')
                    <form method="post" action="{{ $action }}">@csrf
                        @foreach($fields as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                        <input type="hidden" name="{{ $paginator->getPageName() }}" value="{{ $page }}">
                        <button class="pagination-number" type="submit" aria-label="{{ __('lavbe.pagination') }} {{ $page }}">{{ $page }}</button>
                    </form>
                @else
                    <a class="pagination-number" href="{{ $paginator->url($page) }}{{ $fragment ? '#'.$fragment : '' }}" aria-label="{{ __('lavbe.pagination') }} {{ $page }}">{{ $page }}</a>
                @endif
                @php($previous = $page)
            @endforeach

            @if($paginator->hasMorePages() && $method === 'post')
                <form method="post" action="{{ $action }}">@csrf
                    @foreach($fields as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                    <input type="hidden" name="{{ $paginator->getPageName() }}" value="{{ $current + 1 }}">
                    <button class="pagination-control" type="submit">{{ __('lavbe.next') }}</button>
                </form>
            @elseif($paginator->hasMorePages())
                <a class="pagination-control" href="{{ $paginator->nextPageUrl() }}{{ $fragment ? '#'.$fragment : '' }}">{{ __('lavbe.next') }}</a>
            @else
                <span class="pagination-control is-disabled" aria-disabled="true">{{ __('lavbe.next') }}</span>
            @endif
        </div>
    </nav>
@endif
