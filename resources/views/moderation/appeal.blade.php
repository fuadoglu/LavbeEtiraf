@extends('layouts.app')

@section('title', __('moderation.open_appeal'))

@section('content')
    <x-moderation-shell :title="__('moderation.open_appeal')">
        <div class="moderation-detail-grid">
            <div class="moderation-content-column">
                <article class="moderation-story">
                    <div class="moderation-story-label">{{ __('moderation.public_parent') }}</div>
                    <h2>{{ $target->title ?: $target->publicRevision?->title ?: __('moderation.untitled') }}</h2>
                    <div class="confession-body">{!! nl2br(e($target->body ?: $target->publicRevision?->body)) !!}</div>
                </article>
                <article class="moderation-submission">
                    <strong>{{ __('reports.appeal_details') }}</strong>
                    <p>{{ $appeal->details }}</p>
                </article>
            </div>
            <section class="moderation-decision-panel" aria-labelledby="appeal-decision-heading">
                <h2 id="appeal-decision-heading">{{ __('moderation.decision') }}</h2>
                <form class="moderation-decision-form" method="post" action="{{ route('moderation.appeals.decide', ['locale' => request()->route('locale'), 'appealId' => $appeal->id]) }}">
                    @csrf
                    <input type="hidden" name="expected_appeal_lock_version" value="{{ $appeal->lock_version }}">
                    <input type="hidden" name="expected_parent_lock_version" value="{{ $appeal->confession->lock_version }}">
                    <input type="hidden" name="expected_target_lock_version" value="{{ $target->lock_version }}">
                    <input type="hidden" name="revision_id" value="{{ $target->public_revision_id }}">
                    <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                    <x-form-field :label="__('moderation.reason_code')" name="reason_code">
                        <select id="reason_code" name="reason_code" @if($errors->has('reason_code')) aria-describedby="reason_code-description" @endif aria-invalid="{{ $errors->has('reason_code') ? 'true' : 'false' }}" required>
                            @foreach(['privacy','harassment','threat','spam','explicit','legal','other'] as $reason)<option value="{{ $reason }}">{{ __('moderation.reason_'.$reason) }}</option>@endforeach
                        </select>
                    </x-form-field>
                    <div class="moderation-decision-actions">
                        <button class="button button-secondary" name="decision" value="uphold">{{ __('moderation.uphold_appeal') }}</button>
                        <button class="button button-primary" name="decision" value="restore">{{ __('moderation.restore_appealed_content') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </x-moderation-shell>
@endsection
