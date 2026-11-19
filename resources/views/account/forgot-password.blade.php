@extends('layouts.app')
@section('title', __('account.forgot_title'))
@section('content')<section class="write-page"><h1>{{ __('account.forgot_title') }}</h1>@if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
<form class="write-form" method="post" action="{{ route('password.email', ['locale' => request()->route('locale')]) }}">@csrf<x-form-field :label="__('account.email')" name="email"><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></x-form-field><button class="button button-primary" type="submit">{{ __('account.send_reset_link') }}</button></form></section>@endsection
