@extends('layouts.app')

@section('title', __('guest.write_title').' · Lavbe')

@section('content')
    <section class="write-page">
        <header class="write-heading">
            <h1>{{ __('guest.write_title') }}</h1>
            <p class="write-anonymous-note"><x-icon name="shield-check" class="icon-small" />{{ __('guest.write_anonymous') }}</p>
        </header>

        <form class="write-form" method="post" action="{{ route('confessions.store', ['locale' => $lavbeLocale['segment']]) }}">
            @csrf
            <x-form-errors :fields="['body' => 'body', 'title' => 'title', 'category_key' => 'category_key', 'author_display_name' => 'author_display_name', 'response_expectation' => 'response_expectation', 'age_confirmed' => 'age_confirmed', 'remember_device' => 'remember_device']" />
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            <x-form-field name="body" :label="__('guest.write_body')" :hint="__('guest.write_body_hint', ['limit' => config('lavbe.limits.confession_body')])" :error="$errors->first('body')">
                <textarea aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}" id="body" aria-describedby="body-description" name="body" rows="8" maxlength="{{ config('lavbe.limits.confession_body') }}" required>{{ old('body') }}</textarea>
            </x-form-field>

            <x-form-field name="title" :label="__('guest.write_subject')" :error="$errors->first('title')">
                <input @if($errors->has('title')) aria-describedby="title-description" @endif aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}" type="text" id="title" name="title" required maxlength="{{ config('lavbe.limits.confession_title') }}" value="{{ old('title') }}" autocomplete="off">
            </x-form-field>

            <x-form-field name="category_key" :label="__('guest.write_topic')">
                <select @if($errors->has('category_key')) aria-describedby="category_key-description" @endif aria-invalid="{{ $errors->has('category_key') ? 'true' : 'false' }}" id="category_key" name="category_key">
                    <option value="">—</option>
                    @foreach($categories as $category)
                        <option value="{{ $category['key'] }}" @selected(old('category_key') === $category['key'])>{{ $category['label'] }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <details class="topic-guide">
                <summary>{{ __('guest.topic_guide') }}</summary>
                <ul>
                    @foreach($categories as $category)
                        <li><strong><x-icon :name="$category['icon']" class="icon-small" />{{ $category['label'] }}</strong><span>{{ $category['description'] }}</span></li>
                    @endforeach
                </ul>
            </details>

            <details class="sharing-options" @if(old('author_display_name') || $errors->has('author_display_name') || $errors->has('response_expectation') || old('response_expectation')) open @endif>
                <summary>{{ __('guest.write_options') }}</summary>
                <p class="field-description">{{ __('guest.author_name_hint') }}</p>
                <x-form-field name="author_display_name" :label="__('guest.author_name_label')" :error="$errors->first('author_display_name')">
                    <input @if($errors->has('author_display_name')) aria-invalid="true" aria-describedby="author_display_name-description" @endif id="author_display_name" name="author_display_name" maxlength="120" value="{{ old('author_display_name') }}" autocomplete="off">
                </x-form-field>
                <x-form-field name="response_expectation" :label="__('guest.write_expectation')">
                    <select @if($errors->has('response_expectation')) aria-describedby="response_expectation-description" @endif aria-invalid="{{ $errors->has('response_expectation') ? 'true' : 'false' }}" id="response_expectation" name="response_expectation">
                        <option value="">—</option>
                        <option value="share" @selected(old('response_expectation') === 'share')>{{ __('guest.write_share') }}</option>
                        <option value="similar" @selected(old('response_expectation') === 'similar')>{{ __('guest.write_similar') }}</option>
                        <option value="advice" @selected(old('response_expectation') === 'advice')>{{ __('guest.write_advice') }}</option>
                    </select>
                </x-form-field>
            </details>

            <label class="age-confirmation">
                <input id="age_confirmed" @if($errors->has('age_confirmed')) aria-invalid="true" aria-describedby="age-confirmation-error" @endif type="checkbox" name="age_confirmed" value="1" required @checked(old('age_confirmed'))>
                <span>{{ __('guest.write_age') }}</span>
            </label>
            @error('age_confirmed')<p id="age-confirmation-error" class="field-description" role="alert">{{ $message }}</p>@enderror

            <label class="age-confirmation">
                <input id="remember_device" type="checkbox" name="remember_device" value="1" @checked(old('remember_device'))>
                <span>{{ __('guest.write_remember_device') }}</span>
            </label>

            <p class="write-review-note">{{ __('guest.write_public_search_notice') }} {{ __('guest.write_intro') }}</p>
            <button class="button button-primary" type="submit">{{ __('guest.write_submit') }}</button>
        </form>
    </section>
@endsection
