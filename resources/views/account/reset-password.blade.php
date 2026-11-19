@extends('layouts.app')
@section('title', __('account.reset_title'))
@section('content')<section class="write-page"><h1>{{ __('account.reset_title') }}</h1>
<form class="write-form" method="post" action="{{ route('password.update', ['locale' => request()->route('locale')]) }}">@csrf<input type="hidden" name="token" value="{{ $token }}">
<x-form-field :label="__('account.email')" name="email"><input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required></x-form-field>
<x-form-field :label="__('account.password')" name="password"><input type="password" name="password" minlength="12" autocomplete="new-password" required></x-form-field>
<x-form-field :label="__('account.password_confirmation')" name="password_confirmation"><input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required></x-form-field>
<button class="button button-primary" type="submit">{{ __('account.reset_password') }}</button></form></section>@endsection
