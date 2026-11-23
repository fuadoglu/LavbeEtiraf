@extends('layouts.app')

@section('title', __('moderation.revision_title'))

@section('content')
    @php($topic = $revision->category_key ? app(\App\Support\Lavbe\LocaleRegistry::class)->category($revision->category_key) : null)
    <x-moderation-shell :title="__('moderation.revision_title')">
        <div class="moderation-detail-grid">
            <div class="moderation-content-column">
                <dl class="moderation-facts">
                    <div><dt>{{ __('moderation.target_language') }}</dt><dd>{{ strtoupper($confession->locale) }}</dd></div>
                    <div><dt>{{ __('moderation.topic') }}</dt><dd>{{ $topic['labels'][app()->getLocale()] ?? $topic['labels']['en'] ?? '—' }}</dd></div>
                    <div><dt>{{ __('moderation.state') }}</dt><dd>{{ $revision->status === 'pending' ? __('moderation.state_review') : ($confession->status === 'public' ? __('moderation.state_public') : ($confession->status === 'hidden' ? __('moderation.state_hidden') : $confession->status)) }}</dd></div>
                </dl>
                <article class="moderation-story">
                    <div class="moderation-story-label">{{ __('moderation.revision_title') }}</div>
                    <h2>{{ $revision->title ?: __('moderation.untitled') }}</h2>
                    <div class="confession-body">{!! nl2br(e($revision->body)) !!}</div>
                </article>
                @if($publicRevision && $publicRevision->id !== $revision->id)
                    <article class="moderation-story moderation-story--previous">
                        <div class="moderation-story-label">{{ __('moderation.previous_public') }}</div>
                        <h2>{{ $publicRevision->title ?: __('moderation.untitled') }}</h2>
                        <div class="confession-body">{!! nl2br(e($publicRevision->body)) !!}</div>
                        <a href="{{ route('moderation.show', ['locale' => request()->route('locale'), 'confessionId' => $confession->id, 'revision_id' => $publicRevision->id]) }}">{{ __('moderation.review_public_revision') }}</a>
                    </article>
                @endif
            </div>
            <section class="moderation-decision-panel" aria-labelledby="decision-heading">
                <h2 id="decision-heading">{{ __('moderation.decision') }}</h2>
                <form method="post" action="{{ route('moderation.decide', ['locale' => request()->route('locale'), 'confessionId' => $confession->id]) }}" class="moderation-decision-form">
                    @csrf
                    <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                    <input type="hidden" name="expected_lock_version" value="{{ $confession->lock_version }}">
                    <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                    <x-form-field :label="__('moderation.reason_code')" name="reason_code">
                        <select @if($errors->has('reason_code')) aria-describedby="reason_code-description" @endif aria-invalid="{{ $errors->has('reason_code') ? 'true' : 'false' }}" id="reason_code" name="reason_code" required>
                            @foreach(['acceptable','privacy','harassment','threat','spam','explicit','legal','other'] as $reason)
                                <option value="{{ $reason }}" @selected(old('reason_code') === $reason)>{{ __('moderation.reason_'.$reason) }}</option>
                            @endforeach
                        </select>
                    </x-form-field>
                    <x-form-field :label="__('moderation.author_message')" name="author_message" :hint="__('moderation.author_message_required')"><textarea aria-describedby="author_message-description" aria-invalid="{{ $errors->has('author_message') ? 'true' : 'false' }}" id="author_message" name="author_message" rows="4" maxlength="{{ config('lavbe.limits.moderation_author_message') }}">{{ old('author_message') }}</textarea></x-form-field>
                    <x-form-field :label="__('moderation.internal_note')" name="internal_note"><textarea @if($errors->has('internal_note')) aria-describedby="internal_note-description" @endif aria-invalid="{{ $errors->has('internal_note') ? 'true' : 'false' }}" id="internal_note" name="internal_note" rows="3" maxlength="{{ config('lavbe.limits.moderation_internal_note') }}">{{ old('internal_note') }}</textarea></x-form-field>
                    @if($confession->status === 'hidden')
                        <x-form-field :label="__('moderation.restore_reviewed')" name="restore_reviewed"><input id="restore_reviewed" type="checkbox" name="restore_reviewed" value="1" required @checked(old('restore_reviewed'))></x-form-field>
                    @endif
                    <x-form-field :label="__('moderation.decision')" name="decision">
                        <select id="decision" name="decision" @if($errors->has('decision')) aria-describedby="decision-description" @endif aria-invalid="{{ $errors->has('decision') ? 'true' : 'false' }}" required>
                            @if($revision->status === 'pending' && in_array($confession->status, ['unpublished', 'public', 'withdrawn'], true))
                                <option value="approve" @selected(old('decision') === 'approve')>{{ __('moderation.approve') }}</option>
                                <option value="request_changes" @selected(old('decision') === 'request_changes')>{{ __('moderation.request_changes') }}</option>
                                <option value="reject" @selected(old('decision') === 'reject')>{{ __('moderation.reject') }}</option>
                            @endif
                            @if(in_array($confession->status, ['public', 'withdrawn'], true))<option value="hide" @selected(old('decision') === 'hide')>{{ __('moderation.hide') }}</option>@endif
                            @if($confession->status === 'hidden' && $publicRevision)<option value="restore" @selected(old('decision') === 'restore')>{{ __('moderation.restore') }}</option>@endif
                            <option value="terminal_delete" @selected(old('decision') === 'terminal_delete')>{{ __('moderation.terminal_delete') }}</option>
                        </select>
                    </x-form-field>
                    @foreach(['revision_id', 'expected_lock_version', 'idempotency_key'] as $field)@error($field)<p role="alert">{{ $message }}</p>@enderror @endforeach
                    <button class="button button-primary" type="submit">{{ __('moderation.submit_decision') }}</button>
                </form>
            </section>
        </div>
    </x-moderation-shell>
@endsection
