@extends('layouts.app')

@section('title', __('ui.preview').' · '.config('app.name'))

@section('content')
    <section class="stack" aria-labelledby="admin-title">
        <div class="page-heading">
            <h1 id="admin-title">{{ __('moderation.queue_title') }}</h1>
            <p>{{ __('ui.preview_hint') }}</p>
        </div>
        <x-status tone="warning" :title="__('ui.preview')">
            {{ __('ui.preview_hint') }}
        </x-status>
        <nav class="admin-tabs" aria-label="{{ __('moderation.queue_title') }}">
            <a href="#confessions" aria-current="page">{{ __('lavbe.feed_title') }}</a>
            <a href="#comments">{{ __('participation.comment_label') }}</a>
            <a href="#reports">{{ __('reports.community_title') }}</a>
        </nav>
        <article class="story-card stack" id="confessions">
            <div class="hstack muted"><x-icon name="shield-check" /> <span>{{ __('ui.preview_hint') }}</span></div>
            <h2 class="story-title">{{ __('ui.sample_title') }}</h2>
            <p class="story-excerpt">{{ __('ui.sample_text') }}</p>
            <div class="hstack">
                <button class="button" type="button" disabled>{{ __('moderation.approve') }}</button>
                <button class="button button-secondary" type="button" disabled>{{ __('moderation.request_changes') }}</button>
                <button class="button button-secondary" type="button" data-dialog-open="preview-note">{{ __('moderation.internal_note') }}</button>
            </div>
        </article>
        <x-feed-card :topic="__('ui.sample_title')" :title="__('ui.sample_title')" :excerpt="__('ui.sample_text')" :continued="true" />
        <details class="component-sample">
            <summary>{{ __('ui.loading') }}</summary>
            <x-feed-skeleton :count="2" />
        </details>
        <x-form-field :label="__('lavbe.search_title')" name="preview-search" :hint="__('ui.preview_hint')">
            <input aria-describedby="preview-search-description" aria-invalid="{{ $errors->has('preview-search') ? 'true' : 'false' }}" id="preview-search" type="search" placeholder="{{ __('lavbe.search_title') }}" disabled name="preview-search">
        </x-form-field>
    </section>
@endsection

@push('dialogs')
    <x-dialog id="preview-note" :title="__('moderation.internal_note')">
        {{ __('ui.preview_hint') }}
    </x-dialog>
@endpush
