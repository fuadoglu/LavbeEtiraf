@extends('layouts.app')

@php
    $topic = $category ? app(App\Support\Lavbe\LocaleRegistry::class)->category($category) : null;
    $sortLabels = config('lavbe.feed_orders');
    $sortIcons = ['new' => 'sparkles', 'weekly' => 'calendar-days', 'popular' => 'flame'];
@endphp

@section('title', ($topic ? ($topic['labels'][app()->getLocale()] ?? $topic['labels']['en']) : __('lavbe.feed_title')).' · '.config('app.name'))
@if($topic)
    @section('category-icon', $topic['icon'])
@endif

@push('head')
    @include('components.public-analytics', ['pageType' => $topic ? 'topic' : 'home'])
    <link rel="canonical" href="{{ rtrim((string) config('app.url'), '/').$canonical }}">
    @if($confessions->currentPage() > 1 || $sort !== 'new' || ! $categoryIndexable)<meta name="robots" content="noindex,follow">@endif
    @if($confessions->nextPageUrl())<link rel="next" href="{{ $confessions->nextPageUrl() }}">@endif
@endpush

@section('content')
    <section class="feed-page" aria-labelledby="feed-title">
        <div class="feed-layout">
            <aside class="feed-topic-sidebar" aria-label="{{ __('lavbe.categories') }}">
                <x-topic-panel variant="desktop" :selected="$category" :categories="$categories" />
            </aside>
            <div class="feed-main">
                <div class="page-heading feed-heading @if($confessions->isNotEmpty() && ! $topic) feed-heading--populated @endif">
                    <div>
                        <h1 id="feed-title" @if($topic) class="category-title" @endif>{{ $topic ? ($topic['labels'][app()->getLocale()] ?? $topic['labels']['en']) : __('lavbe.feed_title') }}</h1>
                        @if($confessions->isEmpty() || $topic)
                            <p>{{ $topic ? ($topic['descriptions'][app()->getLocale()] ?? $topic['descriptions']['en']) : __('lavbe.feed_subtitle') }}</p>
                        @endif
                    </div>
                </div>

                <div class="feed-controls">
                    <x-topic-panel :selected="$category" :categories="$categories" class="feed-topic-picker-mobile" />
                    <nav class="sort-panel" aria-label="{{ __('lavbe.sort_label') }}">
                @foreach($sortLabels as $key => $label)
                    @php
                        $href = $category
                            ? route('topic.show', ['locale' => $lavbeLocale['segment'], 'category' => $category, 'sort' => $key])
                            : route('home', ['locale' => $lavbeLocale['segment'], 'sort' => $key]);
                    @endphp
                    <a class="sort-link" href="{{ $href }}" @if($sort === $key) aria-current="page" @endif><x-icon :name="$sortIcons[$key]" class="icon-small" /><span class="sort-label-full">{{ __($sortLabels[$key]) }}</span><span class="sort-label-short">{{ $key === 'popular' ? __('lavbe.sort_popular_short') : __($sortLabels[$key]) }}</span></a>
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
            <div class="confession-feed feed-list" aria-label="{{ __('lavbe.feed_title') }}">
                <x-feed-items :confessions="$confessions" />
            </div>
            @if($confessions->hasPages())
                <x-pagination :paginator="$confessions" />
            @endif
        @endif
            </div>
        </div>
    </section>
@endsection
