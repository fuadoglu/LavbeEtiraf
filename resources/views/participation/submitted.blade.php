@extends('layouts.app')

@section('title', __('participation.comment_pending').' · Lavbe')

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('participation.comment_pending') }}</h1>
        @if($recoveryCode !== '')
            <div class="status status-warning">
                <div>
                    <strong>{{ __('participation.comment_recovery') }}</strong>
                    <code dir="ltr">{{ $recoveryCode }}</code>
                    <p>{{ __('participation.comment_recovery_note') }}</p>
                </div>
            </div>
        @endif
        <a href="{{ route('participation.comment.status', ['locale' => $lavbeLocale['segment'], 'commentPublicId' => $comment->public_id]) }}">{{ __('participation.manage_comment') }}</a>
        <a href="{{ route('confessions.public', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">{{ __('lavbe.feed_title') }}</a>
    </section>
@endsection
