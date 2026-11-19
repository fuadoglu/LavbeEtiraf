@extends('layouts.app')

@section('title', __('guest.write_status_title').' · Lavbe')

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('guest.write_status_title') }}</h1>
        <p>{{ $confession->status === 'withdrawn' ? __('guest.write_withdrawn') : __('guest.write_pending_title') }}</p>
        @if($confession->status === 'hidden')<p><a href="{{ route('appeals.create', ['locale' => $lavbeLocale['segment'], 'type' => 'confession', 'targetPublicId' => $confession->public_id]) }}">{{ __('reports.appeal_title') }}</a></p>@endif
        <p class="submission-alias">{{ $confession->author_alias }}</p>
        @if($confession->revisions->isNotEmpty())
            <article class="owner-revision">
                @if($confession->revisions->first()->title)<h2>{{ $confession->revisions->first()->title }}</h2>@endif
                <p>{{ $confession->revisions->first()->body }}</p>
            </article>
        @endif
        <a href="{{ route('confessions.recovery.form', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">{{ __('guest.write_recover_title') }}</a>
        @if(in_array($confession->status, ['public', 'unpublished'], true))
            <form method="post" action="{{ route('confessions.reviews', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">
                @csrf
                <input type="hidden" name="open" value="{{ $confession->reviews_open ? '0' : '1' }}">
                <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                <button class="button button-secondary" type="submit">{{ $confession->reviews_open ? __('participation.reviews_close') : __('participation.reviews_open') }}</button>
            </form>
        @endif
        @if(in_array($confession->status, ['unpublished', 'public'], true))
            <form method="post" action="{{ route('confessions.withdraw', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">
                @csrf
                <input type="hidden" name="expected_lock_version" value="{{ $confession->lock_version }}">
                <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                <x-form-field :label="__('guest.write_confirm_withdraw')" name="confirm_withdraw"><input type="checkbox" id="confirm_withdraw" name="confirm_withdraw" value="1" required></x-form-field>
                <button class="button button-secondary" type="submit">{{ __('guest.write_withdraw') }}</button>
            </form>
        @elseif($confession->status === 'withdrawn')
            <p>{{ __('guest.write_republish_pending') }}</p>
            @if($confession->public_revision_id && ! $confession->republish_requested_at)
                <form method="post" action="{{ route('confessions.republish', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">
                    @csrf
                    <input type="hidden" name="expected_lock_version" value="{{ $confession->lock_version }}">
                    <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                    <x-form-field :label="__('guest.write_confirm_republish')" name="confirm_republish"><input type="checkbox" id="confirm_republish" name="confirm_republish" value="1" required></x-form-field>
                    <button class="button button-primary" type="submit">{{ __('guest.write_republish') }}</button>
                </form>
            @endif
        @endif
    </section>
@endsection
