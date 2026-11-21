@extends('layouts.app')

@section('title', __('guest.write_recovery_success').' · '.config('app.name'))

@section('content')
    <section class="write-page submission-result">
        <h1>{{ __('guest.write_recovery_success') }}</h1>
        <p>{{ __('guest.write_recovery_once') }}</p>
        <code dir="ltr">{{ $recoveryCode }}</code>
        <a href="{{ route('confessions.status', ['locale' => $lavbeLocale['segment'], 'publicId' => $confession->public_id]) }}">{{ __('guest.write_owner_link') }}</a>
    </section>
@endsection
