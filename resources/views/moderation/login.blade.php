@extends('layouts.app')

@section('title', __('moderation.login_title'))

@section('content')
    <section class="write-page moderation-auth">
        <h1>{{ __('moderation.login_title') }}</h1>
        <p>{{ __('moderation.login_intro') }}</p>
        <form method="post" action="{{ route('moderation.login.submit', ['locale' => request()->route('locale')]) }}">
            @csrf
            <x-form-field :label="__('moderation.email')" name="email"><input @if($errors->has('email')) aria-describedby="email-description" @endif aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required></x-form-field>
            <x-form-field :label="__('moderation.password')" name="password"><input @if($errors->has('password')) aria-describedby="password-description" @endif aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" id="password" type="password" name="password" autocomplete="current-password" required></x-form-field>
            <button class="button button-primary" type="submit">{{ __('moderation.login_submit') }}</button>
        </form>
    </section>
@endsection
