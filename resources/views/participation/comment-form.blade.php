@extends('layouts.app')

@section('title', __('participation.comment_label').' · '.config('app.name'))

@push('head')
    <meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
<section class="write-page" aria-labelledby="comment-form-title">
    <h1 id="comment-form-title">{{ __('participation.comment_label') }}</h1>
    <p><a href="{{ route('confessions.public', ['locale' => $lavbeLocale['segment'], 'publicId' => $story->public_id]) }}">{{ $story->title ?: __('lavbe.untitled_confession') }}</a></p>
    @if($story->reviews_open)
        <form method="post" action="{{ route('confessions.comments.store', ['locale' => $lavbeLocale['segment'], 'publicId' => $story->public_id]) }}" class="participation-form">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
            <input type="hidden" name="comment_scope" value="standalone">
            <x-form-field :label="__('participation.comment_label')" name="body" id="comment-body" :error="old('_comment_body_error') ?: $errors->first('body')"><textarea id="comment-body" name="body" @if(old('_comment_body_error') || $errors->has('body')) autofocus aria-describedby="comment-body-description" @endif required aria-invalid="{{ old('_comment_body_error') || $errors->has('body') ? 'true' : 'false' }}">{{ old('body') }}</textarea></x-form-field>
            <label class="age-confirmation"><input id="remember_device" type="checkbox" name="remember_device" value="1" @checked(old('remember_device'))><span>{{ __('participation.remember_device') }}</span></label>
            <button class="button button-primary" type="submit">{{ __('participation.comment_submit') }}</button>
        </form>
    @else
        <p>{{ __('participation.comments_closed') }}</p>
    @endif
</section>
@endsection
