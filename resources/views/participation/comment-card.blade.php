@php
    $isReacted = in_array($comment->id, $reactedCommentIds, true);
    $hasOldReply = $allowReply && old('comment_scope') === 'reply' && old('parent_public_id') === $comment->public_id;
    $replyError = $hasOldReply ? (old('_comment_body_error') ?: $errors->first('body')) : null;
    $hasOldResponse = old('response_comment_public_id') === $comment->public_id;
    $responseError = $hasOldResponse ? (old('_response_body_error') ?: $errors->first('body')) : null;
@endphp
<article id="comment-{{ $comment->public_id }}" class="participation-card comment-card{{ $nested ? ' comment-reply-card' : '' }}">
    <header class="comment-card-header">
        <p class="story-meta">{{ $comment->author_alias }} <span aria-hidden="true">·</span> <time datetime="{{ $comment->published_at->toAtomString() }}">{{ $comment->published_at->format('Y-m-d') }}</time></p>
    </header>
    <div class="confession-body">{!! nl2br(e($comment->publicRevision->body)) !!}</div>

    <div class="comment-card-actions">
        <form method="post" action="{{ route('confessions.comments.reaction', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id, 'commentPublicId' => $comment->public_id]) }}">
            @csrf
            <input type="hidden" name="action" value="{{ $isReacted ? 'unreact' : 'react' }}">
            <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
            <button class="button button-secondary comment-reaction" type="submit" aria-pressed="{{ $isReacted ? 'true' : 'false' }}">
                <x-icon name="heart" />
                <span>{{ __($isReacted ? 'participation.comment_reaction_remove' : 'participation.comment_reaction') }}</span>
                <span class="comment-reaction-count">{{ $comment->reaction_count }}</span>
            </button>
        </form>
        @if(in_array($comment->id, $ownedCommentIds, true))
            <a href="{{ route('participation.comment.status', ['locale' => $localeRecord['segment'], 'commentPublicId' => $comment->public_id]) }}">{{ __('participation.manage_comment') }}</a>
        @endif
        <a href="{{ route('reports.create', ['locale' => $localeRecord['segment'], 'type' => 'comment', 'targetPublicId' => $comment->public_id]) }}">{{ __('reports.report_comment') }}</a>
    </div>

    @if($comment->response?->status === 'public' && $comment->response->publicRevision?->status === 'approved')
        <aside class="author-response">
            <strong>{{ __('participation.author_response') }}</strong>
            <div class="confession-body">{!! nl2br(e($comment->response->publicRevision->body)) !!}</div>
        </aside>
    @elseif($ownerCanParticipate && $comment->status === 'public')
        <form method="post" action="{{ route('confessions.comment.response', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id, 'commentPublicId' => $comment->public_id]) }}" class="comment-inline-form">
            @csrf
            <input type="hidden" name="response_comment_public_id" value="{{ $comment->public_id }}">
            <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
            <x-form-field :label="__('participation.author_response')" name="body" :id="'response-body-'.$comment->id" :error="$responseError">
                <textarea id="response-body-{{ $comment->id }}" name="body" @if($responseError) autofocus aria-describedby="response-body-{{ $comment->id }}-description" @endif required>{{ $hasOldResponse ? old('body') : '' }}</textarea>
            </x-form-field>
            <button class="button button-secondary" type="submit">{{ __('participation.comment_submit') }}</button>
        </form>
    @endif

    @if($allowReply && $commentsOpen)
        <details class="comment-reply-composer" @if($hasOldReply) open @endif>
            <summary>{{ __('participation.comment_reply') }}</summary>
            <form method="post" action="{{ route('confessions.comments.store', ['locale' => $localeRecord['segment'], 'publicId' => $confession->public_id]) }}" class="participation-form">
                @csrf
                <input type="hidden" name="comment_scope" value="reply">
                <input type="hidden" name="parent_public_id" value="{{ $comment->public_id }}">
                <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                <x-form-field :label="__('participation.comment_label')" name="body" :id="'reply-body-'.$comment->id" :error="$replyError">
                <textarea id="reply-body-{{ $comment->id }}" name="body" maxlength="{{ config('lavbe.limits.comment_body') }}" @if($replyError) autofocus aria-describedby="reply-body-{{ $comment->id }}-description" @endif aria-invalid="{{ $replyError ? 'true' : 'false' }}" required>{{ $hasOldReply ? old('body') : '' }}</textarea>
                </x-form-field>
                <label class="age-confirmation"><input id="remember-reply-{{ $comment->id }}" type="checkbox" name="remember_device" value="1"><span>{{ __('participation.remember_device') }}</span></label>
                <button class="button button-primary" type="submit">{{ __('participation.comment_submit') }}</button>
            </form>
        </details>
    @endif
</article>
