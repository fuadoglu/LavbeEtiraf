@extends('layouts.app')

@section('title', __('lavbe.search_title').' · Lavbe')

@push('head')
    <meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
    <section class="stack" aria-labelledby="search-title">
        <h1 id="search-title">{{ __('lavbe.search_title') }}</h1>
        <form method="post" action="{{ route('search', ['locale' => $lavbeLocale['segment']]) }}" class="form-field" data-search-form>
            @csrf
            <label for="feed-search">{{ __('lavbe.search_label') }}</label>
            <input id="feed-search" type="search" name="q" value="" maxlength="{{ config('lavbe.limits.search_query') }}" autocomplete="off">
            <button class="button" type="submit">{{ __('lavbe.search_submit') }}</button>
        </form>
        @if($results)
            @if($results->isEmpty())
                <p>{{ __('lavbe.search_empty') }}</p>
            @else
                <div class="feed-list"><x-feed-items :confessions="$results" /></div>
                @if($results->hasPages())
                    <nav aria-label="{{ __('lavbe.pagination') }}" class="sort-panel">
                        @if($results->previousPageUrl())<a href="{{ $results->previousPageUrl() }}">{{ __('lavbe.previous') }}</a>@endif
                        @if($results->nextPageUrl())<a rel="next" href="{{ $results->nextPageUrl() }}">{{ __('lavbe.next') }}</a>@endif
                    </nav>
                @endif
            @endif
        @endif
    </section>
@endsection
