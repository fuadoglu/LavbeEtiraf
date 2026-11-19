@extends('layouts.app')

@section('title', __('guest.write_status_title').' · Lavbe')

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('guest.write_status_title') }}</h1>
        <p>{{ __('guest.write_pending_title') }}</p>
        <p class="submission-alias">{{ $confession->author_alias }}</p>
        @if($confession->revisions->isNotEmpty())
            <article class="owner-revision">
                @if($confession->revisions->first()->title)<h2>{{ $confession->revisions->first()->title }}</h2>@endif
                <p>{{ $confession->revisions->first()->body }}</p>
            </article>
        @endif
        <a href="{{ route('confessions.recovery.form', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">{{ __('guest.write_recover_title') }}</a>
    </section>
@endsection
