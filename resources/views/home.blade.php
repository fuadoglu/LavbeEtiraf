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
            <p>{{ $topic ? ($topic['descriptions'][app()->getLocale()] ?? $topic['descriptions']['en']) : __('lavbe.feed_subtitle') }}</p>
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
            <div class="confession-feed feed-list" aria-label="{{ __('lavbe.feed_title') }}">
                <x-feed-items :confessions="$confessions" />
            </div>
            @if($confessions->hasPages())
                <x-pagination :paginator="$confessions" />
            @endif
        @endif
    </section>
@endsection
