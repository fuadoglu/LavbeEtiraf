@extends('layouts.app')

@section('title', __('moderation.reports_queue'))

@section('content')
    <x-moderation-shell :title="__('moderation.reports_queue')">
        <div class="moderation-list">
            @forelse($items as $group)
                <article class="moderation-card">
                    <div class="moderation-card-meta">
                        <span class="moderation-pill moderation-pill--{{ $group->priority }}">{{ __('moderation.priority_'.$group->priority) }}</span>
                        <span>{{ __('moderation.report_channel_'.$group->channel) }}</span>
                        <span>{{ strtoupper($group->confession->locale) }}</span>
                    </div>
                    <div class="moderation-card-main">
                        <div><h2>{{ $group->confession->title ?: __('moderation.untitled') }}</h2><p>{{ __('moderation.report_count') }}: {{ $group->report_count }}</p></div>
                        <a class="moderation-open" href="{{ route('moderation.reports.show', ['locale' => request()->route('locale'), 'groupId' => $group->id]) }}">{{ __('moderation.open_report') }} <x-icon name="arrow-left" class="icon-small" /></a>
                    </div>
                </article>
            @empty
                <div class="moderation-empty"><x-icon name="circle-check" /><p>{{ __('moderation.reports_empty') }}</p></div>
            @endforelse
        </div>
        <x-pagination :paginator="$items" />
    </x-moderation-shell>
@endsection
