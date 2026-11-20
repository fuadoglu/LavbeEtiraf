@extends('layouts.app')

@section('title', __('guest.write_recover_title').' · Lavbe')

@section('content')
    <section class="write-page">
        <h1>{{ __('guest.write_recover_title') }}</h1>
        <form class="write-form" method="post" action="{{ route('confessions.recovery', ['locale' => $lavbeLocale['segment'], 'publicId' => $publicId]) }}">
            @csrf
            <x-form-field name="recovery_code" :label="__('guest.write_recovery_code')" :error="$errors->first('recovery_code')">
                <input @if($errors->has('recovery_code')) aria-describedby="recovery_code-description" @endif aria-invalid="{{ $errors->has('recovery_code') ? 'true' : 'false' }}" id="recovery_code" type="text" name="recovery_code" required minlength="{{ config('lavbe.limits.recovery_code_min') }}" maxlength="{{ config('lavbe.limits.recovery_code') }}" autocomplete="off" autocapitalize="none" spellcheck="false" dir="ltr">
            </x-form-field>
            <label class="age-confirmation">
                <input type="checkbox" name="remember_device" value="1">
                <span>{{ __('guest.write_remember_device') }}</span>
            </label>
            <button class="button button-primary" type="submit">{{ __('guest.write_recover_submit') }}</button>
        </form>
    </section>
@endsection
