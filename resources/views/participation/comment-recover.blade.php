@extends('layouts.app')

@section('title', __('participation.recover_comment').' · Lavbe')

@section('content')
    <section class="write-page">
        <h1>{{ __('participation.recover_comment') }}</h1>
        <form method="post" action="{{ route('participation.comment.recovery', ['locale' => $lavbeLocale['segment'], 'commentPublicId' => $commentPublicId]) }}">
            @csrf
            <x-form-field :label="__('participation.comment_recovery')" name="recovery_code"><input @if($errors->has('recovery_code')) aria-describedby="recovery_code-description" @endif aria-invalid="{{ $errors->has('recovery_code') ? 'true' : 'false' }}" id="recovery_code" name="recovery_code" type="text" autocomplete="off" required></x-form-field>
            <x-form-field :label="__('participation.remember_device')" name="remember_device"><input @if($errors->has('remember_device')) aria-describedby="remember_device-description" @endif aria-invalid="{{ $errors->has('remember_device') ? 'true' : 'false' }}" id="remember_device" name="remember_device" type="checkbox" value="1"></x-form-field>
            <button class="button button-primary" type="submit">{{ __('guest.write_recover_submit') }}</button>
        </form>
    </section>
@endsection
