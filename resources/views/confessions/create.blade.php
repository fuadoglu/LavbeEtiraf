@extends('layouts.app')

@section('title', __('guest.write_title').' · '.config('app.name'))

@section('content')
    <section class="write-page">
        <header class="write-heading">
            <h1>{{ __('guest.write_title') }}</h1>
            <p class="write-anonymous-note"><x-icon name="shield-check" class="icon-small" />{{ __('guest.write_anonymous') }}</p>
        </header>

        <form class="write-form confession-write-form" method="post" action="{{ route('confessions.store', ['locale' => $lavbeLocale['segment']]) }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

            <x-form-field name="body" :label="__('guest.write_body')" :hint="__('guest.write_body_hint', ['limit' => config('lavbe.limits.confession_body')])" :error="$errors->first('body')">
                <textarea aria-invalid="{{ old('_form_errors.body') || $errors->has('body') ? 'true' : 'false' }}" @if(old('_focus_field') === 'body' || ($errors->has('body') && ! $errors->has('title'))) autofocus @endif id="body" aria-describedby="body-description body-remaining" name="body" rows="8" data-character-count-input required>{{ old('body') }}</textarea>
                <p class="character-counter"><output id="body-remaining" for="body" data-character-count data-max="{{ config('lavbe.limits.confession_body') }}">{{ max(0, (int) config('lavbe.limits.confession_body') - mb_strlen((string) old('body'), 'UTF-8')) }}</output> {{ __('guest.characters_remaining') }}</p>
            </x-form-field>

            <x-form-field name="title" :label="__('guest.write_subject')" :error="$errors->first('title')">
                <input @if(old('_form_errors.title') || $errors->has('title')) aria-describedby="title-description" @endif @if(old('_focus_field') === 'title' || $errors->has('title')) autofocus @endif aria-invalid="{{ old('_form_errors.title') || $errors->has('title') ? 'true' : 'false' }}" type="text" id="title" name="title" required maxlength="{{ config('lavbe.limits.confession_title') }}" value="{{ old('title') }}" autocomplete="off">
            </x-form-field>

            <x-form-field name="category_key" :label="__('guest.write_topic')">
                <div class="write-select">
                    <select @if(old('_form_errors.category_key') || $errors->has('category_key')) aria-describedby="category_key-description" @endif aria-invalid="{{ old('_form_errors.category_key') || $errors->has('category_key') ? 'true' : 'false' }}" id="category_key" name="category_key">
                        <option value="">—</option>
                        @foreach($categories as $category)
                            <option value="{{ $category['key'] }}" @selected(old('category_key') === $category['key'])>{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" class="icon-small" />
                </div>
            </x-form-field>
            <details class="topic-guide">
                <summary>{{ __('guest.topic_guide') }}</summary>
                <ul>
                    @foreach($categories as $category)
                        <li><div class="topic-guide-name"><x-icon :name="$category['icon']" class="icon-small" />{{ $category['label'] }}</div><span>{{ $category['description'] }}</span></li>
                    @endforeach
                </ul>
            </details>

            <section class="sharing-options" aria-labelledby="sharing-options-heading">
                <h2 id="sharing-options-heading">{{ __('guest.write_options') }}</h2>
                <p class="field-description">{{ __('guest.author_name_hint') }}</p>
                <x-form-field name="author_display_name" :label="__('guest.author_name_label')" :error="$errors->first('author_display_name')">
                    <input id="author_display_name" name="author_display_name" maxlength="{{ config('lavbe.limits.author_display_name') }}" value="{{ old('author_display_name') }}" autocomplete="off">
                </x-form-field>
                <x-form-field name="response_expectation" :label="__('guest.write_expectation')">
                    <div class="write-select">
                        <select @if(old('_form_errors.response_expectation') || $errors->has('response_expectation')) aria-describedby="response_expectation-description" @endif aria-invalid="{{ old('_form_errors.response_expectation') || $errors->has('response_expectation') ? 'true' : 'false' }}" id="response_expectation" name="response_expectation">
                            <option value="">—</option>
                            <option value="share" @selected(old('response_expectation') === 'share')>{{ __('guest.write_share') }}</option>
                            <option value="similar" @selected(old('response_expectation') === 'similar')>{{ __('guest.write_similar') }}</option>
                            <option value="advice" @selected(old('response_expectation') === 'advice')>{{ __('guest.write_advice') }}</option>
                        </select>
                        <x-icon name="chevron-down" class="icon-small" />
                    </div>
                </x-form-field>
            </section>

            <div class="age-confirmation">
                <input id="age_confirmed" type="checkbox" name="age_confirmed" value="1" required aria-labelledby="age-confirmation-label" @if(old('_form_errors.age_confirmed') || $errors->has('age_confirmed')) aria-invalid="true" aria-describedby="age-confirmation-error" @endif @if(old('_focus_field') === 'age_confirmed') autofocus @endif @checked(old('age_confirmed'))>
                @php([$beforeRules, $afterRules] = explode(':rules', __('guest.write_age'), 2))
                <span id="age-confirmation-label"><label for="age_confirmed">{{ $beforeRules }}</label><a class="write-rules-link" href="{{ route('legal.page', ['locale' => $lavbeLocale['segment'], 'page' => 'community']) }}">{{ __('guest.write_rules') }}</a><label for="age_confirmed">{{ $afterRules }}</label></span>
            </div>
            @if(old('_form_errors.age_confirmed') || $errors->has('age_confirmed'))<p id="age-confirmation-error" class="field-description" role="alert">{{ old('_form_errors.age_confirmed') ?: $errors->first('age_confirmed') }}</p>@endif

            <label class="age-confirmation">
                <input type="checkbox" name="remember_device" value="1" @checked(old('remember_device'))>
                <span>{{ __('guest.write_remember_device') }}</span>
            </label>

            <p class="write-review-note">{{ __('guest.write_public_search_notice') }} {{ __('guest.write_intro') }}</p>
            <button class="button button-primary" type="submit">{{ __('guest.write_submit') }}</button>
        </form>
    </section>
@endsection
