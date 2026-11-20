@extends('layouts.app')
@section('title', __('account.forgot_title'))
@section('content')
    <section class="write-page">
        <h1>{{ __('account.forgot_title') }}</h1>
        @if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
        <form class="write-form" method="post" action="{{ route('password.email', ['locale' => request()->route('locale')]) }}">
            @csrf
            <x-form-field :label="__('account.email')" name="email">
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @if($errors->has('email')) aria-describedby="email-description" @endif>
            </x-form-field>
            <button class="button button-primary" type="submit">{{ __('account.send_reset_link') }}</button>
        </form>
        <a class="button button-secondary password-back-link" href="{{ route('account.login', ['locale' => request()->route('locale')]) }}"><x-icon name="arrow-left" class="icon-small" />{{ __('account.login_title') }}</a>
    </section>
@endsection
