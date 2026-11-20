@extends('layouts.app')

@php
    $topic = $category ? app(App\Support\Lavbe\LocaleRegistry::class)->category($category) : null;
    $sortLabels = config('lavbe.feed_orders');
@endphp

@section('title', ($topic ? ($topic['labels'][app()->getLocale()] ?? $topic['labels']['en']) : __('lavbe.feed_title')).' · Lavbe')

@push('head')
    <link rel="canonical" href="{{ rtrim((string) config('app.url'), '/').$canonical }}">
    @if($confessions->currentPage() > 1 || $sort !== 'new' || ! $categoryIndexable)<meta name="robots" content="noindex,follow">@endif
    @if($confessions->nextPageUrl())<link rel="next" href="{{ $confessions->nextPageUrl() }}">@endif
@endpush

@section('content')
    <section aria-labelledby="feed-title">
        <div class="page-heading feed-heading">
            <div>
            <h1 id="feed-title">{{ $topic ? ($topic['labels'][app()->getLocale()] ?? $topic['labels']['en']) : __('lavbe.feed_title') }}</h1>
            <p>{{ __('lavbe.feed_subtitle') }}</p>
            </div>
        </div>

        <div class="feed-controls">
            <x-topic-panel :selected="$category" :categories="$categories" />
            <nav class="sort-panel" aria-label="{{ __('lavbe.sort_label') }}">
                @foreach($sortLabels as $key => $label)
                    @php
                        $href = $category
                            ? route('topic.show', ['locale' => $lavbeLocale['segment'], 'category' => $category, 'sort' => $key])
                            : route('home', ['locale' => $lavbeLocale['segment'], 'sort' => $key]);
                    @endphp
                    <a class="sort-link" href="{{ $href }}" @if($sort === $key) aria-current="page" @endif>{{ __($sortLabels[$key]) }}</a>
                @endforeach
            </nav>
        </div>

        @if($confessions->isEmpty())
            <div class="empty-state">
                <span class="empty-mark"><x-icon name="book-open" class="icon-large" /></span>
                <h2>{{ $category ? __('lavbe.category_empty') : __('lavbe.feed_empty') }}</h2>
                <p>{{ __('lavbe.feed_empty_description') }}</p>
                <a class="button" href="{{ route('confessions.create', ['locale' => $lavbeLocale['segment']]) }}"><x-icon name="pen-line" />{{ __('guest.write_title') }}</a>
            </div>
        @else
            <div class="confession-feed feed-list" aria-label="{{ __('lavbe.feed_title') }}" data-feed data-next-cursor="{{ $feedCursor }}">
                <x-feed-items :confessions="$confessions" />
            </div>
            @if($confessions->hasPages())
            <nav aria-label="{{ __('lavbe.pagination') }}" class="sort-panel">
                    @if($confessions->previousPageUrl()) <a href="{{ $confessions->previousPageUrl() }}">{{ __('lavbe.previous') }}</a> @endif
                    @if($confessions->nextPageUrl()) <a href="{{ $confessions->nextPageUrl() }}">{{ __('lavbe.next') }}</a> @endif
                </nav>
            @endif
        @endif
        @if($feedCursor && $confessions->total() > config('feed.page_size'))
            <button type="button" class="button button-secondary" data-feed-more data-endpoint="{{ route('feed.fragment', ['locale' => $lavbeLocale['segment'], 'order' => $sort, 'category' => $category]) }}">
                {{ __('lavbe.next') }}
            </button>
            <button type="button" class="muted" data-feed-status data-retry-label="{{ __('lavbe.retry') }}" data-refresh-label="{{ __('lavbe.refresh') }}" hidden aria-live="polite"></button>
        @endif
    </section>
@endsection
