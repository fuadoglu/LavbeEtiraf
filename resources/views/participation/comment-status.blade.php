@extends('layouts.app')

@section('title', __('participation.manage_comment').' · Lavbe')

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('participation.manage_comment') }}</h1>
        <p>{{ __('participation.comment_state', ['state' => __('state.'.$comment->status)]) }}</p>
        @if($comment->status === 'hidden')<p><a href="{{ route('appeals.create', ['locale' => $lavbeLocale['segment'], 'type' => 'comment', 'targetPublicId' => $comment->public_id]) }}">{{ __('reports.appeal_title') }}</a></p>@endif
        @if($comment->revisions->isNotEmpty())
            <article class="owner-revision">
                <p>{{ $comment->author_alias }}</p>
                <p>{{ $comment->revisions->first()->body }}</p>
            </article>
        @endif
        <a href="{{ route('participation.comment.recovery.form', ['locale' => $lavbeLocale['segment'], 'commentPublicId' => $comment->public_id]) }}">{{ __('participation.recover_comment') }}</a>
        @if(auth()->check() && auth()->user()->hasVerifiedEmail() && ! $comment->ownership?->user_id)
            <form method="post" action="{{ route('account.comments.claim', ['locale' => $lavbeLocale['segment'], 'publicId' => $comment->public_id]) }}">@csrf<button class="button button-secondary" type="submit">{{ __('account.claim') }}</button></form>
        @endif
        <form method="post" action="{{ route('participation.comment.withdraw', ['locale' => $lavbeLocale['segment'], 'commentPublicId' => $comment->public_id]) }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
            <x-form-field :label="__('participation.withdraw_comment_confirm')" name="confirm_withdraw"><input @if($errors->has('confirm_withdraw')) aria-describedby="confirm_withdraw-description" @endif aria-invalid="{{ $errors->has('confirm_withdraw') ? 'true' : 'false' }}" id="confirm_withdraw" type="checkbox" name="confirm_withdraw" value="1" required></x-form-field>
            <button class="button button-secondary" type="submit">{{ __('participation.withdraw_comment') }}</button>
        </form>
        <a href="{{ route('confessions.public', ['locale' => $lavbeLocale['segment'], 'publicId' => $comment->confession->public_id]) }}">{{ __('lavbe.feed_title') }}</a>
    </section>
@endsection
