@extends('layouts.app')

@section('title', (($revision->title ?: mb_substr($revision->body, 0, config('lavbe.public_title_excerpt'), 'UTF-8')).' · Lavbe'))
@if($category)
    @section('favicon', asset('icons/lucide/'.$category['icon'].'.svg'))
@endif

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
        <nav class="confession-breadcrumb" aria-label="{{ __('lavbe.breadcrumb') }}">
            <a href="{{ route('home', ['locale' => $localeRecord['segment']]) }}">{{ __('lavbe.feed_title') }}</a>
            @if($category)
                <span aria-hidden="true">›</span>
                <a href="{{ route('topic.show', ['locale' => $localeRecord['segment'], 'category' => $revision->category_key]) }}">{{ $category['labels'][$localeRecord['code']] ?? $category['labels']['en'] }}</a>
            @endif
        </nav>
        <div class="confession-reading-card">
        <header class="page-heading confession-heading">
            @if($category)<p class="story-meta">{{ $category['labels'][$localeRecord['code']] ?? $category['labels']['en'] }}</p>@endif
            <h1 id="confession-title">{{ $revision->title ?: __('lavbe.untitled_confession') }}</h1>
            <p class="story-meta">{{ $revision->author_display_name ?: $confession->author_alias }} <span aria-hidden="true">·</span> <time datetime="{{ $confession->published_at->toAtomString() }}">{{ $confession->published_at->format('Y-m-d') }}</time><span aria-hidden="true">·</span><span class="unique-read-count"><x-icon name="eye" class="icon-small" />{{ __('participation.unique_reads', ['count' => number_format((int) $confession->unique_read_count)]) }}</span></p>
        </header>
        <div class="confession-body">{!! nl2br(e($revision->body)) !!}</div>
        <div class="confession-report-links">
        <p><a href="{{ route('reports.create', ['locale' => $localeRecord['segment'], 'type' => 'confession', 'targetPublicId' => $confession->public_id]) }}" data-dialog-open="confession-report-dialog">{{ __('reports.report_confession') }}</a></p>
        <p><a href="{{ route('reports.legal.create', ['locale' => $localeRecord['segment'], 'type' => 'confession', 'targetPublicId' => $confession->public_id]) }}" data-dialog-open="confession-legal-dialog">{{ __('reports.legal_title') }}</a></p>

        </div>
        </div>

        <div class="confession-actions">
        <form method="post" action="{{ route('confessions.support', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}" class="participation-action">
            @csrf
            <input type="hidden" name="action" value="{{ $hasSupported ? 'unsupport' : 'support' }}">
            <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
            <button class="button button-secondary" aria-pressed="{{ $hasSupported ? 'true' : 'false' }}" type="submit"><x-icon name="heart" />{{ __($hasSupported ? 'participation.unsupport' : 'participation.support') }}</button>
            <span class="muted">{{ __('participation.support_count', ['count' => $confession->support_count]) }}</span>
        </form>
        @if(auth()->check() && auth()->user()->hasVerifiedEmail())
            <form method="post" action="{{ route('account.follow', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}" class="participation-action">@csrf<button class="button button-secondary" type="submit">{{ __('account.follow') }}</button></form>
        @else
            <p><a href="{{ route('account.login', ['locale' => $localeRecord['segment']]) }}">{{ __('account.login_to_follow') }}</a></p>
        @endif

        </div>

        <section class="participation-section confession-comments" aria-labelledby="comments-heading" id="comments">
            <h2 id="comments-heading">{{ __('participation.comments_heading') }}</h2>
            @forelse($comments as $comment)
                @include('participation.comment-card', ['comment' => $comment, 'allowReply' => true, 'nested' => false, 'confession' => $confession, 'localeRecord' => $localeRecord, 'ownerCanParticipate' => $ownerCanParticipate, 'commentsOpen' => $confession->reviews_open, 'ownedCommentIds' => $ownedCommentIds, 'reactedCommentIds' => $reactedCommentIds])
                @if($threadRoot?->id === $comment->id && $threadReplies)
                    <div class="comment-replies" aria-label="{{ __('participation.comment_replies_count', ['count' => $comment->public_replies_count]) }}">
                        @foreach($threadReplies as $reply)
                            @include('participation.comment-card', ['comment' => $reply, 'allowReply' => false, 'nested' => true, 'confession' => $confession, 'localeRecord' => $localeRecord, 'ownerCanParticipate' => $ownerCanParticipate, 'commentsOpen' => $confession->reviews_open, 'ownedCommentIds' => $ownedCommentIds, 'reactedCommentIds' => $reactedCommentIds])
                        @endforeach
                        @if($threadReplies->hasPages())
                            <x-pagination :paginator="$threadReplies" :fragment="'comment-'.$comment->public_id" />
                        @endif
                    </div>
                @else
                    @foreach($comment->replies as $reply)
                        @include('participation.comment-card', ['comment' => $reply, 'allowReply' => false, 'nested' => true, 'confession' => $confession, 'localeRecord' => $localeRecord, 'ownerCanParticipate' => $ownerCanParticipate, 'commentsOpen' => $confession->reviews_open, 'ownedCommentIds' => $ownedCommentIds, 'reactedCommentIds' => $reactedCommentIds])
                    @endforeach
                    @if($comment->public_replies_count > $comment->replies->count())
                        <a class="comment-more-replies" href="{{ request()->fullUrlWithQuery(['thread' => $comment->public_id, 'reply_page' => 1]) }}#comment-{{ $comment->public_id }}">{{ __('participation.comment_replies_count', ['count' => $comment->public_replies_count]) }}</a>
                    @endif
                @endif
            @empty
                <p class="muted">{{ __('participation.comments_empty') }}</p>
            @endforelse
            @if($comments->hasPages())
                <x-pagination :paginator="$comments" :fragment="'comments-heading'" />
            @endif

            @if($confession->reviews_open)
                <form id="comment-composer" method="post" action="{{ route('confessions.comments.store', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}" class="participation-form comment-composer">
                    @csrf
                    <input type="hidden" name="comment_scope" value="inline">
                    <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                    <x-form-field :label="__('participation.comment_label')" name="body" id="comment-body-new" :error="old('comment_scope') === 'inline' ? $errors->first('body') : null">
                        <textarea id="comment-body-new" name="body" maxlength="{{ config('lavbe.limits.comment_body') }}" @if(old('comment_scope') === 'inline' && $errors->has('body')) aria-describedby="comment-body-new-description" @endif aria-invalid="{{ old('comment_scope') === 'inline' && $errors->has('body') ? 'true' : 'false' }}" required>{{ old('comment_scope') === 'inline' ? old('body') : '' }}</textarea>
                    </x-form-field>
                    <label class="age-confirmation"><input id="remember-comment-device" type="checkbox" name="remember_device" value="1"><span>{{ __('participation.remember_device') }}</span></label>
                    <button class="button button-primary" type="submit">{{ __('participation.comment_submit') }}</button>
                </form>
            @else
                <p class="muted">{{ __('participation.comments_closed') }}</p>
            @endif
        </section>

        @if($recommended->isNotEmpty())
            <section class="participation-section" aria-labelledby="recommended-heading">
                <h2 id="recommended-heading">{{ __('participation.recommended_heading') }}</h2>
                <div class="confession-feed feed-list"><x-feed-items :confessions="$recommended" /></div>
            </section>
        @endif

        @if($continuations->isNotEmpty())
            <section class="participation-section" aria-labelledby="continuation-heading">
                <h2 id="continuation-heading">{{ __('participation.continuation_heading') }}</h2>
                @foreach($continuations as $continuation)
                    <article class="participation-card">
                        <div class="confession-body">{!! nl2br(e($continuation->publicRevision->body)) !!}</div>
                        <p class="muted"><time datetime="{{ $continuation->published_at->toAtomString() }}">{{ $continuation->published_at->format('Y-m-d') }}</time></p>
                    </article>
                @endforeach
                <x-pagination :paginator="$continuations" :fragment="'continuation-heading'" />
            </section>
        @endif

        @if($ownerCanParticipate)
            <section class="participation-section" aria-labelledby="owner-continuation-heading">
                <h2 id="owner-continuation-heading">{{ __('participation.continuation_heading') }}</h2>
                <form method="post" action="{{ route('confessions.continuations.store', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                    <x-form-field :label="__('participation.continuation_label')" name="body" id="continuation-body"><textarea @if($errors->has('body')) aria-describedby="continuation-body-description" @endif aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}" id="continuation-body" name="body" maxlength="{{ config('lavbe.limits.continuation_body') }}" required></textarea></x-form-field>
                    <label class="age-confirmation"><input id="confirm_continuation" type="checkbox" name="confirm_continuation" value="1" required><span>{{ __('participation.continuation_confirm') }}</span></label>
                    <button class="button button-primary" type="submit">{{ __('participation.comment_submit') }}</button>
                </form>
            </section>
        @endif
    </article>

    @push('dialogs')
        <x-dialog id="confession-report-dialog" :title="__('reports.community_title')">
            <p>{{ __('reports.target_confession') }}: {{ $revision->title }}</p>
            @include('reports.form', ['channel' => 'community', 'confession' => $confession, 'targetType' => 'confession', 'targetPublicId' => $confession->public_id, 'locale' => $localeRecord['segment'], 'idempotencyKey' => bin2hex(random_bytes(16))])
        </x-dialog>
        <x-dialog id="confession-legal-dialog" :title="__('reports.legal_title')">
            <p>{{ __('reports.target_confession') }}: {{ $revision->title }}</p>
            <x-status tone="warning">{{ __('reports.legal_separation') }}</x-status>
            @include('reports.form', ['channel' => 'legal', 'confession' => $confession, 'targetType' => 'confession', 'targetPublicId' => $confession->public_id, 'locale' => $localeRecord['segment'], 'idempotencyKey' => bin2hex(random_bytes(16))])
        </x-dialog>
    @endpush
@endsection
