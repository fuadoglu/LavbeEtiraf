@extends('layouts.app')

@section('title', __('moderation.queue_title'))

@section('content')
    <section class="write-page">
        <header class="page-heading"><h1>{{ __('moderation.queue_title') }}</h1>
            <div>
                @if($isAdmin)<a class="button button-secondary" href="{{ route('moderation.grants', ['locale' => request()->route('locale')]) }}">{{ __('moderation.grant_title') }}</a>@endif
                <a class="button button-secondary" href="{{ route('moderation.participation', ['locale' => request()->route('locale')]) }}">{{ __('moderation.participation_queue') }}</a>
                <a class="button button-secondary" href="{{ route('moderation.reports', ['locale' => request()->route('locale')]) }}">{{ __('moderation.reports_queue') }}</a>
                <a class="button button-secondary" href="{{ route('moderation.appeals', ['locale' => request()->route('locale')]) }}">{{ __('moderation.appeals_queue') }}</a>
                <form method="post" action="{{ route('moderation.logout', ['locale' => request()->route('locale')]) }}">@csrf<button class="button button-secondary" type="submit">{{ __('moderation.logout') }}</button></form>
            </div>
        </header>
        @if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
        @if($allowed->count() > 1)
            <nav class="topic-panel" aria-label="{{ __('moderation.target_language') }}">
                <a href="{{ route('moderation.queue', ['locale' => request()->route('locale')]) }}">{{ __('moderation.target_language') }} · {{ __('moderation.all_languages') }}</a>
                @foreach($allowed as $language)<a href="{{ route('moderation.queue', ['locale' => request()->route('locale'), 'language' => $language]) }}">{{ $language }}</a>@endforeach
            </nav>
        @endif
        <nav class="topic-panel" aria-label="{{ __('moderation.state') }}">
            @foreach(['review' => 'state_review', 'public' => 'state_public', 'hidden' => 'state_hidden'] as $state => $label)
                <a href="{{ route('moderation.queue', ['locale' => request()->route('locale'), 'language' => $selected ?: null, 'state' => $state]) }}" @if($selectedState === $state) aria-current="page" @endif>{{ __('moderation.'.$label) }}</a>
            @endforeach
        </nav>
        @forelse($items as $item)
            <article class="feed-card">
                <p>{{ __('moderation.target_language') }}: {{ $item->locale }} · {{ __('moderation.topic') }}: {{ $item->category_key ?? '—' }}</p>
                <h2>{{ $item->title ?: __('moderation.untitled') }}</h2>
                <a class="button button-primary" href="{{ route('moderation.show', ['locale' => request()->route('locale'), 'confessionId' => $item->id, 'revision_id' => $selectedState === 'review' ? null : $item->revision_id]) }}">{{ __('moderation.open_review') }}</a>
            </article>
        @empty
            <x-status>{{ __('moderation.queue_empty') }}</x-status>
        @endforelse
        {{ $items->links() }}
    </section>
@endsection
