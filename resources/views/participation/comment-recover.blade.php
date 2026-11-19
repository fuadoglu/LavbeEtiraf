@extends('layouts.app')

@section('title', __('guest.write_recover_title').' · Lavbe')

@section('content')
    <section class="write-page">
        <h1>{{ __('guest.write_recover_title') }}</h1>
        <form method="post" action="{{ route('participation.comment.recovery', ['locale' => $lavbeLocale['segment'], 'commentPublicId' => $commentPublicId]) }}">
            @csrf
            <x-form-field :label="__('participation.comment_recovery')" name="recovery_code"><input id="recovery_code" name="recovery_code" type="text" autocomplete="off" required></x-form-field>
            <x-form-field :label="__('participation.remember_device')" name="remember_device"><input id="remember_device" name="remember_device" type="checkbox" value="1"></x-form-field>
            <button class="button button-primary" type="submit">{{ __('guest.write_recover_submit') }}</button>
        </form>
    </section>
@endsection
