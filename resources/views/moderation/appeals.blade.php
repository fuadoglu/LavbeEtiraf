@extends('layouts.app')

@section('title', __('moderation.appeals_queue'))

@section('content')
    <x-moderation-shell :title="__('moderation.appeals_queue')">
        <div class="moderation-list">
            @forelse($items as $appeal)
                <article class="moderation-card">
                    <div class="moderation-card-meta">
                        <span class="moderation-pill">{{ strtoupper($appeal->confession->locale) }}</span>
                        <time datetime="{{ $appeal->created_at->toDateString() }}">{{ $appeal->created_at->translatedFormat('d M Y') }}</time>
                    </div>
                    <div class="moderation-card-main">
                        <h2>{{ $appeal->confession->title ?: __('moderation.untitled') }}</h2>
                        <a class="moderation-open" href="{{ route('moderation.appeals.show', ['locale' => request()->route('locale'), 'appealId' => $appeal->id]) }}">{{ __('moderation.open_appeal') }} <x-icon name="arrow-left" class="icon-small" /></a>
                    </div>
                </article>
            @empty
                <div class="moderation-empty"><x-icon name="circle-check" /><p>{{ __('moderation.appeals_empty') }}</p></div>
            @endforelse
        </div>
        <x-pagination :paginator="$items" />
    </x-moderation-shell>
@endsection
