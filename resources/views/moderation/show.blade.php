@extends('layouts.app')

@section('title', __('moderation.revision_title'))

@section('content')
    <section class="write-page">
        @php($topic = $revision->category_key ? app(\App\Support\Lavbe\LocaleRegistry::class)->category($revision->category_key) : null)
        <a href="{{ route('moderation.queue', ['locale' => request()->route('locale')]) }}">{{ __('moderation.back_to_queue') }}</a>
        <h1>{{ __('moderation.revision_title') }}</h1>
        <dl>
            <dt>{{ __('moderation.target_language') }}</dt><dd>{{ $confession->locale }}</dd>
            <dt>{{ __('moderation.topic') }}</dt><dd>{{ $topic['labels'][app()->getLocale()] ?? $topic['labels']['en'] ?? '—' }} @if($topic)<span class="field-description">{{ $topic['descriptions'][app()->getLocale()] ?? $topic['descriptions']['en'] }}</span>@endif</dd>
            <dt>{{ __('moderation.state') }}</dt><dd>{{ $confession->status }} · {{ $revision->status }} · {{ $confession->lock_version }}</dd>
        </dl>
        @if($publicRevision && $publicRevision->id !== $revision->id)
            <h2>{{ __('moderation.previous_public') }}</h2>
            <h3>{{ $publicRevision->title }}</h3>
            <p class="confession-body">{{ $publicRevision->body }}</p>
            <a href="{{ route('moderation.show', ['locale' => request()->route('locale'), 'confessionId' => $confession->id, 'revision_id' => $publicRevision->id]) }}">{{ __('moderation.review_public_revision') }}</a>
        @endif
        <h2>{{ $revision->title }}</h2>
        <p class="confession-body">{{ $revision->body }}</p>
        <form method="post" action="{{ route('moderation.decide', ['locale' => request()->route('locale'), 'confessionId' => $confession->id]) }}" class="write-form">
            @csrf
            <input type="hidden" name="revision_id" value="{{ $revision->id }}">
            <input type="hidden" name="expected_lock_version" value="{{ $confession->lock_version }}">
            <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
            <x-form-field :label="__('moderation.reason_code')" name="reason_code">
                <select @if($errors->has('reason_code')) aria-describedby="reason_code-description" @endif aria-invalid="{{ $errors->has('reason_code') ? 'true' : 'false' }}" id="reason_code" name="reason_code" required>
                    <option value="acceptable">{{ __('moderation.reason_acceptable') }}</option>
                    <option value="privacy">{{ __('moderation.reason_privacy') }}</option>
                    <option value="harassment">{{ __('moderation.reason_harassment') }}</option>
                    <option value="threat">{{ __('moderation.reason_threat') }}</option>
                    <option value="spam">{{ __('moderation.reason_spam') }}</option>
                    <option value="explicit">{{ __('moderation.reason_explicit') }}</option>
                    <option value="legal">{{ __('moderation.reason_legal') }}</option>
                    <option value="other">{{ __('moderation.reason_other') }}</option>
                </select>
            </x-form-field>
            <x-form-field :label="__('moderation.author_message')" name="author_message" :hint="__('moderation.author_message_required')"><textarea aria-describedby="author_message-description" aria-invalid="{{ $errors->has('author_message') ? 'true' : 'false' }}" id="author_message" name="author_message" rows="4" maxlength="{{ config('lavbe.limits.moderation_author_message') }}"></textarea></x-form-field>
            <x-form-field :label="__('moderation.internal_note')" name="internal_note"><textarea @if($errors->has('internal_note')) aria-describedby="internal_note-description" @endif aria-invalid="{{ $errors->has('internal_note') ? 'true' : 'false' }}" id="internal_note" name="internal_note" rows="3" maxlength="{{ config('lavbe.limits.moderation_internal_note') }}"></textarea></x-form-field>
            @if($confession->status === 'hidden')
                <x-form-field :label="__('moderation.restore_reviewed')" name="restore_reviewed"><input @if($errors->has('restore_reviewed')) aria-describedby="restore_reviewed-description" @endif aria-invalid="{{ $errors->has('restore_reviewed') ? 'true' : 'false' }}" id="restore_reviewed" type="checkbox" name="restore_reviewed" value="1" required></x-form-field>
            @endif
            <label for="decision">{{ __('moderation.decision') }}</label>
            <select id="decision" name="decision" required>
                @if($revision->status === 'pending' && in_array($confession->status, ['unpublished', 'public', 'withdrawn'], true))
                    <option value="approve">{{ __('moderation.approve') }}</option>
                    <option value="request_changes">{{ __('moderation.request_changes') }}</option>
                    <option value="reject">{{ __('moderation.reject') }}</option>
                @endif
                @if(in_array($confession->status, ['public', 'withdrawn'], true))<option value="hide">{{ __('moderation.hide') }}</option>@endif
                @if($confession->status === 'hidden' && $publicRevision)<option value="restore">{{ __('moderation.restore') }}</option>@endif
                <option value="terminal_delete">{{ __('moderation.terminal_delete') }}</option>
            </select>
            @foreach(['revision_id', 'expected_lock_version', 'idempotency_key'] as $field)@error($field)<p role="alert">{{ $message }}</p>@enderror @endforeach
            <button class="button button-primary" type="submit">{{ __('moderation.submit_decision') }}</button>
        </form>
    </section>
@endsection
