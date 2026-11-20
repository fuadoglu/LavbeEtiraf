@extends('layouts.app')

@section('title', __('ui.preview').' · Lavbe')

@section('content')
    <section class="stack" aria-labelledby="admin-title">
        <div class="page-heading">
            <h1 id="admin-title">{{ __('ui.preview') }}</h1>
            <p>{{ __('ui.preview_hint') }}</p>
        </div>
        <x-status tone="warning" :title="__('ui.preview')">
            {{ __('ui.preview_hint') }}
        </x-status>
        <nav class="admin-tabs" aria-label="{{ __('ui.nav') }}">
            <a href="#confessions" aria-current="page">{{ __('account.my_confessions') }}</a>
            <a href="#comments">{{ __('participation.comments_heading') }}</a>
            <a href="#reports">{{ __('moderation.reports_queue') }}</a>
        </nav>
        <article class="story-card stack" id="confessions">
            <div class="hstack muted"><x-icon name="shield-check" /> <span>{{ __('state.pending') }}</span></div>
            <h2 class="story-title">{{ __('ui.sample_title') }}</h2>
            <p class="story-excerpt">{{ __('ui.sample_text') }}</p>
            <div class="hstack">
                <button class="button" type="button" disabled>{{ __('moderation.approve') }}</button>
                <button class="button button-secondary" type="button" disabled>{{ __('moderation.request_changes') }}</button>
                <button class="button button-secondary" type="button" data-dialog-open="preview-note">{{ __('moderation.internal_note') }}</button>
            </div>
        </article>
        <x-feed-card :topic="__('guest.write_topic')" :title="__('ui.sample_title')" :excerpt="__('ui.sample_text')" :continued="true" />
        <details class="component-sample">
            <summary>{{ __('ui.loading') }}</summary>
            <x-feed-skeleton :count="2" />
        </details>
        <x-form-field :label="__('lavbe.search_title')" name="preview-search" :hint="__('ui.preview_hint')">
            <input id="preview-search" aria-describedby="preview-search-description" type="search" placeholder="{{ __('lavbe.search_label') }}" disabled>
        </x-form-field>
    </section>
@endsection

@push('dialogs')
    <x-dialog id="preview-note" :title="__('ui.preview')">
        {{ __('ui.preview_hint') }}
    </x-dialog>
@endpush
