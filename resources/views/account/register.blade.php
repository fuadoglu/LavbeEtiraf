@extends('layouts.app')
@section('title', __('account.register_title'))
@section('content')<section class="write-page"><h1>{{ __('account.register_title') }}</h1>
<form class="write-form" method="post" action="{{ route('account.register.store', ['locale' => request()->route('locale')]) }}">@csrf
<x-form-field :label="__('account.name')" name="name"><input type="text" name="name" maxlength="{{ config('lavbe.limits.account_name') }}" value="{{ old('name') }}" autocomplete="name" required></x-form-field>
<x-form-field :label="__('account.email')" name="email"><input type="email" name="email" maxlength="{{ config('lavbe.limits.account_email') }}" value="{{ old('email') }}" autocomplete="email" required></x-form-field>
<x-form-field :label="__('account.password')" name="password"><input type="password" name="password" minlength="{{ config('lavbe.limits.account_password_min') }}" autocomplete="new-password" required></x-form-field>
<x-form-field :label="__('account.password_confirmation')" name="password_confirmation"><input type="password" name="password_confirmation" minlength="{{ config('lavbe.limits.account_password_min') }}" autocomplete="new-password" required></x-form-field>
<button class="button button-primary" type="submit">{{ __('account.register') }}</button></form>
<p>{{ __('account.have_account') }} <a href="{{ route('account.login', ['locale' => request()->route('locale')]) }}">{{ __('account.login') }}</a></p></section>@endsection
