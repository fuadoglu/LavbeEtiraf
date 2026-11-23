@extends('layouts.app')

@section('title', __('moderation.settings_title'))

@section('content')
    <x-moderation-shell :title="__('moderation.settings_title')">
        <div class="moderation-compose">
            <p>{{ __('moderation.settings_intro') }}</p>
            <form method="post" action="{{ route('moderation.settings.update', ['locale' => request()->route('locale')]) }}" class="moderation-panel">
                @csrf
                <input type="hidden" name="expected_lock_version" value="{{ $settings->lock_version }}">
                <div class="moderation-panel-heading"><h2>{{ __('moderation.settings_ga4') }}</h2></div>
                <x-form-field name="ga4_measurement_id" :label="__('moderation.settings_ga4_id')" :error="$errors->first('ga4_measurement_id')">
                    <input id="ga4_measurement_id" name="ga4_measurement_id" value="{{ old('ga4_measurement_id', $settings->ga4_measurement_id) }}" pattern="G-[A-Z0-9]{6,20}" autocomplete="off">
                </x-form-field>
                <label class="age-confirmation"><input type="checkbox" name="ga4_enabled" value="1" @checked(old('ga4_enabled', $settings->ga4_enabled))><span>{{ __('moderation.settings_ga4_enabled') }}</span></label>
                <div class="moderation-panel-heading"><h2>{{ __('moderation.settings_ads') }}</h2></div>
                <x-form-field name="adsense_client_id" :label="__('moderation.settings_ads_client')" :error="$errors->first('adsense_client_id')">
                    <input id="adsense_client_id" name="adsense_client_id" value="{{ old('adsense_client_id', $settings->adsense_client_id) }}" pattern="ca-pub-[0-9]{16}" autocomplete="off">
                </x-form-field>
                <x-form-field name="story_slot_id" :label="__('moderation.settings_story_slot')" :error="$errors->first('story_slot_id')">
                    <input id="story_slot_id" name="story_slot_id" value="{{ old('story_slot_id', $settings->story_slot_id) }}" pattern="[0-9]{6,20}" autocomplete="off">
                </x-form-field>
                <label class="age-confirmation"><input type="checkbox" name="story_slot_enabled" value="1" @checked(old('story_slot_enabled', $settings->story_slot_enabled))><span>{{ __('moderation.settings_slot_enabled') }}</span></label>
                <label class="age-confirmation"><input type="checkbox" name="ads_enabled" value="1" @checked(old('ads_enabled', $settings->ads_enabled))><span>{{ __('moderation.settings_ads_enabled') }}</span></label>
                <p class="field-description">{{ __('moderation.settings_ads_gate') }}</p>
                <button class="button button-primary" type="submit">{{ __('moderation.settings_save') }}</button>
            </form>
        </div>
    </x-moderation-shell>
@endsection
