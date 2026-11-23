@extends('layouts.app')

@section('title', __('moderation.open_report'))

@section('content')
    <x-moderation-shell :title="__('moderation.open_report')">
        <div class="moderation-detail-grid">
            <div class="moderation-content-column">
                <div class="moderation-card-meta">
                    <span class="moderation-pill moderation-pill--{{ $group->priority }}">{{ __('moderation.priority_'.$group->priority) }}</span>
                    <span>{{ __('moderation.report_channel_'.$group->channel) }}</span>
                    <span>{{ __('moderation.report_count') }}: {{ $group->report_count }}</span>
                </div>
                <article class="moderation-story">
                    <div class="moderation-story-label">{{ __('moderation.public_parent') }}</div>
                    <h2>{{ $target->title ?: $target->publicRevision?->title ?: __('moderation.untitled') }}</h2>
                    <div class="confession-body">{!! nl2br(e($target->body ?: $target->publicRevision?->body)) !!}</div>
                </article>
                <section aria-labelledby="report-submissions" class="moderation-submissions">
                    <h2 id="report-submissions">{{ __('moderation.report_submissions') }}</h2>
                    @forelse($submissions as $submission)
                        <article class="moderation-submission">
                            <strong>{{ __('reports.reason_'.$submission->reason_code) }}</strong>
                            @if($submission->details)<p>{{ $submission->details }}</p>@endif
                        </article>
                    @empty
                        <p class="muted">—</p>
                    @endforelse
                    <x-pagination :paginator="$submissions" :fragment="'report-submissions'" />
                </section>
            </div>
            <section class="moderation-decision-panel" aria-labelledby="report-decision-heading">
                <h2 id="report-decision-heading">{{ __('moderation.decision') }}</h2>
                <form class="moderation-decision-form" method="post" action="{{ route('moderation.reports.decide', ['locale' => request()->route('locale'), 'groupId' => $group->id]) }}">
                    @csrf
                    <input type="hidden" name="expected_group_lock_version" value="{{ $group->lock_version }}">
                    <input type="hidden" name="expected_parent_lock_version" value="{{ $group->confession->lock_version }}">
                    <input type="hidden" name="expected_target_lock_version" value="{{ $group->target_type === 'confession' ? $group->confession->lock_version : $target->lock_version }}">
                    <input type="hidden" name="revision_id" value="{{ $target->public_revision_id }}">
                    <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                    <x-form-field :label="__('moderation.reason_code')" name="reason_code">
                        <select id="reason_code" name="reason_code" @if($errors->has('reason_code')) aria-describedby="reason_code-description" @endif aria-invalid="{{ $errors->has('reason_code') ? 'true' : 'false' }}" required>
                            <option value="no_violation">{{ __('moderation.reason_no_violation') }}</option>
                            @foreach(['privacy','harassment','threat','spam','explicit','legal','other'] as $reason)<option value="{{ $reason }}">{{ __('moderation.reason_'.$reason) }}</option>@endforeach
                        </select>
                    </x-form-field>
                    <div class="moderation-decision-actions">
                        <button class="button button-secondary" name="decision" value="dismiss">{{ __('moderation.dismiss_report') }}</button>
                        <button class="button button-primary" name="decision" value="hide">{{ __('moderation.hide_reported_content') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </x-moderation-shell>
@endsection
