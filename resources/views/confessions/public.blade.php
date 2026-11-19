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

        <form method="post" action="{{ route('confessions.support', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}" class="participation-action">
            @csrf
            <input type="hidden" name="action" value="{{ $hasSupported ? 'unsupport' : 'support' }}">
            <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
            <button class="button button-secondary" type="submit">{{ __($hasSupported ? 'participation.unsupport' : 'participation.support') }}</button>
            <span class="muted">{{ __('participation.support_count', ['count' => $confession->support_count]) }}</span>
        </form>

        @if($continuations->isNotEmpty())
            <section class="participation-section" aria-labelledby="continuation-heading">
                <h2 id="continuation-heading">{{ __('participation.continuation_heading') }}</h2>
                @foreach($continuations as $continuation)
                    <article class="participation-card">
                        <div class="confession-body">{!! nl2br(e($continuation->publicRevision->body)) !!}</div>
                        <p class="muted"><time datetime="{{ $continuation->published_at->toAtomString() }}">{{ $continuation->published_at->format('Y-m-d') }}</time></p>
                    </article>
                @endforeach
            </section>
        @endif

        <section class="participation-section" aria-labelledby="comments-heading">
            <h2 id="comments-heading">{{ __('participation.comments_heading') }}</h2>
            @forelse($comments as $comment)
                <article class="participation-card">
                    <p class="story-meta">{{ $comment->author_alias }}</p>
                    <div class="confession-body">{!! nl2br(e($comment->publicRevision->body)) !!}</div>
                    <p class="muted"><time datetime="{{ $comment->published_at->toAtomString() }}">{{ $comment->published_at->format('Y-m-d') }}</time></p>
                    @if(in_array($comment->id, $ownedCommentIds, true))
                        <a href="{{ route('participation.comment.status', ['locale' => $localeRecord['segment'], 'commentPublicId' => $comment->public_id]) }}">{{ __('participation.manage_comment') }}</a>
                    @endif
                    @if($comment->response?->status === 'public' && $comment->response->publicRevision?->status === 'approved')
                        <aside class="author-response">
                            <strong>{{ __('participation.author_response') }}</strong>
                            <div class="confession-body">{!! nl2br(e($comment->response->publicRevision->body)) !!}</div>
                        </aside>
                    @elseif($ownerCanParticipate && $comment->status === 'public')
                        <form method="post" action="{{ route('confessions.comment.response', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id, 'commentPublicId' => $comment->public_id]) }}">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                            <x-form-field :label="__('participation.author_response')" name="body" :id="'response-body-'.$comment->id"><textarea name="body" maxlength="1200" required></textarea></x-form-field>
                            <button class="button button-secondary" type="submit">{{ __('participation.comment_submit') }}</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="muted">{{ __('participation.comments_empty') }}</p>
            @endforelse

            @if($confession->reviews_open)
                <form method="post" action="{{ route('confessions.comments.store', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}" class="participation-form">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                    <x-form-field :label="__('participation.comment_label')" name="body" id="comment-body"><textarea name="body" maxlength="1200" required></textarea></x-form-field>
                    <x-form-field :label="__('participation.remember_device')" name="remember_device"><input id="remember_device" type="checkbox" name="remember_device" value="1"></x-form-field>
                    <button class="button button-primary" type="submit">{{ __('participation.comment_submit') }}</button>
                </form>
            @else
                <p class="muted">{{ __('participation.comments_closed') }}</p>
            @endif
        </section>

        @if($ownerCanParticipate)
            <section class="participation-section" aria-labelledby="owner-continuation-heading">
                <h2 id="owner-continuation-heading">{{ __('participation.continuation_heading') }}</h2>
                <form method="post" action="{{ route('confessions.continuations.store', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                    <x-form-field :label="__('participation.continuation_label')" name="body"><textarea id="continuation-body" name="body" maxlength="6000" required></textarea></x-form-field>
                    <x-form-field :label="__('participation.continuation_confirm')" name="confirm_continuation"><input id="confirm_continuation" type="checkbox" name="confirm_continuation" value="1" required></x-form-field>
                    <button class="button button-primary" type="submit">{{ __('participation.comment_submit') }}</button>
                </form>
            </section>
        @endif
    </article>
@endsection
