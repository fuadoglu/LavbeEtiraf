@extends('layouts.app')
@section('title', __('account.verification_title'))
@section('content')<section class="write-page"><h1>{{ __('account.verification_title') }}</h1><p>{{ __('account.verification_body') }}</p>@if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
<form method="post" action="{{ route('verification.send', ['locale' => request()->route('locale')]) }}">@csrf<button class="button button-primary" type="submit">{{ __('account.resend_verification') }}</button></form></section>@endsection
