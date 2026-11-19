@extends('layouts.app')

@section('title', __('guest.write_title').' · Lavbe')

@section('content')
    <section class="write-page">
        <header class="write-heading">
            <h1>{{ __('guest.write_title') }}</h1>
            <p>{{ __('guest.write_intro') }}</p>
        </header>

        <form class="write-form" method="post" action="{{ route('confessions.store', ['locale' => $lavbeLocale['segment']]) }}" data-offline-draft-form data-draft-locale="{{ $lavbeLocale['segment'] }}" data-draft-expired="{{ $offlineDraft['expired'] }}" data-draft-saved="{{ $offlineDraft['saved'] }}" data-storage-error="{{ $offlineDraft['storageError'] }}" data-network-error="{{ $offlineDraft['networkError'] }}" data-has-old-input="{{ old('body') !== null ? 'true' : 'false' }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            <x-form-field name="title" :label="__('guest.write_subject')" :error="$errors->first('title')">
                <input type="text" name="title" value="{{ old('title') }}" autocomplete="off">
            </x-form-field>

            <x-form-field name="body" :label="__('guest.write_body')" :hint="__('guest.write_body_hint')" :error="$errors->first('body')">
                <textarea name="body" rows="8" required>{{ old('body') }}</textarea>
            </x-form-field>

            <x-form-field name="category_key" :label="__('guest.write_topic')">
                <select name="category_key">
                    <option value="">—</option>
                    @foreach($categories as $category)
                        <option value="{{ $category['key'] }}" @selected(old('category_key') === $category['key'])>{{ $category['label'] }}</option>
                    @endforeach
                </select>
            </x-form-field>

            <x-form-field name="response_expectation" :label="__('guest.write_expectation')">
                <select name="response_expectation">
                    <option value="">—</option>
                    <option value="share" @selected(old('response_expectation') === 'share')>{{ __('guest.write_share') }}</option>
                    <option value="similar" @selected(old('response_expectation') === 'similar')>{{ __('guest.write_similar') }}</option>
                    <option value="advice" @selected(old('response_expectation') === 'advice')>{{ __('guest.write_advice') }}</option>
                </select>
            </x-form-field>

            <label class="age-confirmation">
                <input type="checkbox" name="age_confirmed" value="1" required @checked(old('age_confirmed'))>
                <span>{{ __('guest.write_age') }}</span>
            </label>
            @error('age_confirmed')<p class="field-description" role="alert">{{ $message }}</p>@enderror

            <label class="age-confirmation">
                <input type="checkbox" name="remember_device" value="1" @checked(old('remember_device'))>
                <span>{{ __('guest.write_remember_device') }}</span>
            </label>

            <label class="age-confirmation">
                <input type="checkbox" name="remember_draft_on_device" value="1" data-draft-consent>
                <span>{{ $offlineDraft['label'] }}</span>
            </label>
            <p class="field-description" data-draft-status role="status" aria-live="polite" hidden></p>

            <button class="button button-primary" type="submit">{{ __('guest.write_submit') }}</button>
        </form>
    </section>
@endsection
