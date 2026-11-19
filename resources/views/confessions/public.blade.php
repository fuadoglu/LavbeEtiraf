@extends('layouts.app')

@section('title', (($revision->title ?: mb_substr($revision->body, 0, 70, 'UTF-8')).' · Lavbe'))

@push('head')
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="description" content="{{ $description }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $revision->title ?: __('lavbe.feed_title') }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $localeRecord['code']) }}">
@endpush

@section('content')
    <article class="confession-detail" aria-labelledby="confession-title">
        <nav aria-label="{{ __('lavbe.breadcrumb') }}">
            <a href="{{ route('home', ['locale' => $localeRecord['segment']]) }}">{{ __('lavbe.feed_title') }}</a>
            @if($category)
                <span aria-hidden="true">›</span>
                <a href="{{ route('topic.show', ['locale' => $localeRecord['segment'], 'category' => $revision->category_key]) }}">{{ $category['labels'][$localeRecord['code']] ?? $category['labels']['en'] }}</a>
            @endif
        </nav>
        <header class="page-heading">
            @if($category)<p>{{ $category['labels'][$localeRecord['code']] ?? $category['labels']['en'] }}</p>@endif
            <h1 id="confession-title">{{ $revision->title ?: __('lavbe.untitled_confession') }}</h1>
            <p>{{ $confession->author_alias }}</p>
        </header>
        <div class="confession-body">{!! nl2br(e($revision->body)) !!}</div>
        <p class="muted"><time datetime="{{ $confession->published_at->toAtomString() }}">{{ $confession->published_at->format('Y-m-d') }}</time></p>
    </article>
@endsection
