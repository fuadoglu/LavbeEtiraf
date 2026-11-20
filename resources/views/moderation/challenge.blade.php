@extends('layouts.app')

@section('title', __('moderation.challenge_title'))

@section('content')
    <section class="write-page">
        <h1>{{ __('moderation.challenge_title') }}</h1>
        <p>{{ __('moderation.challenge_body') }}</p>
        <form method="post" action="{{ route('moderation.mfa.verify', ['locale' => request()->route('locale')]) }}">
            @csrf
            <x-form-field :label="__('moderation.verify_code')" name="code"><input @if($errors->has('code')) aria-describedby="code-description" @endif aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}" id="code" type="text" name="code" autocomplete="one-time-code" required></x-form-field>
            @error('code')<p role="alert">{{ $message }}</p>@enderror
            <button class="button button-primary" type="submit">{{ __('moderation.verify') }}</button>
        </form>
        <form method="post" action="{{ route('moderation.logout', ['locale' => request()->route('locale')]) }}">@csrf<button class="button button-secondary" type="submit">{{ __('moderation.logout') }}</button></form>
    </section>
@endsection
