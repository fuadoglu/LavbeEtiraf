@extends('layouts.app')

@section('title', __('guest.write_pending_title').' · Lavbe')

@section('content')
    <section class="write-page submission-result" data-confession-submit-success data-draft-locale="{{ $lavbeLocale['segment'] }}">
        <h1>{{ __('guest.write_pending_title') }}</h1>
        <p>{{ __('guest.write_pending_body') }}</p>
        <p class="submission-alias">{{ $confession->author_alias }}</p>
        <a href="{{ route('confessions.status', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">{{ __('guest.write_owner_link') }}</a>
        <div class="recovery-code" aria-labelledby="recovery-heading">
            <h2 id="recovery-heading">{{ __('guest.write_recovery_title') }}</h2>
            <p>{{ __('guest.write_recovery_once') }}</p>
            <code dir="ltr">{{ $recoveryCode }}</code>
        </div>
    </section>
@endsection
