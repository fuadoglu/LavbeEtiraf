@extends('layouts.app')
@section('title', __('account.login_title'))
@section('content')<section class="write-page"><h1>{{ __('account.login_title') }}</h1>@if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
<form class="write-form" method="post" action="{{ route('account.login.store', ['locale' => request()->route('locale')]) }}">@csrf
<x-form-field :label="__('account.email')" name="email"><input id="email" type="email" name="email" maxlength="{{ config('lavbe.limits.account_email') }}" value="{{ old('email') }}" autocomplete="username" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @if($errors->has('email')) aria-describedby="email-description" @endif></x-form-field>
<x-form-field :label="__('account.password')" name="password"><input id="password" type="password" name="password" autocomplete="current-password" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if($errors->has('password')) aria-describedby="password-description" @endif></x-form-field>
<x-form-field :label="__('account.remember')" name="remember"><input id="remember" type="checkbox" name="remember" value="1" aria-invalid="{{ $errors->has('remember') ? 'true' : 'false' }}" @if($errors->has('remember')) aria-describedby="remember-description" @endif></x-form-field>
<button class="button button-primary" type="submit">{{ __('account.login') }}</button></form>
<p><a href="{{ route('password.request', ['locale' => request()->route('locale')]) }}">{{ __('account.forgot_password') }}</a></p><p><a href="{{ route('account.register', ['locale' => request()->route('locale')]) }}">{{ __('account.need_account') }}</a></p></section>@endsection
