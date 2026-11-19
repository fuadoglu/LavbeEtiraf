@extends('layouts.app')

@section('title', __('moderation.login_title'))

@section('content')
    <section class="write-page">
        <h1>{{ __('moderation.login_title') }}</h1>
        <p>{{ __('moderation.login_intro') }}</p>
        <form method="post" action="{{ route('moderation.login.submit', ['locale' => request()->route('locale')]) }}">
            @csrf
            <x-form-field :label="__('moderation.email')" name="email"><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required></x-form-field>
            @error('email')<p role="alert">{{ $message }}</p>@enderror
            <x-form-field :label="__('moderation.password')" name="password"><input type="password" name="password" autocomplete="current-password" required></x-form-field>
            @error('password')<p role="alert">{{ $message }}</p>@enderror
            <button class="button button-primary" type="submit">{{ __('moderation.login_submit') }}</button>
        </form>
    </section>
@endsection
