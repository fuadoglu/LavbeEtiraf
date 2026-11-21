@extends('layouts.app')

@section('title', __('moderation.participation_queue'))

@section('content')
    <section class="write-page">
        <header class="page-heading">
            <h1>{{ __('moderation.participation_queue') }}</h1>
            <a href="{{ route('moderation.queue', ['locale' => request()->route('locale')]) }}">{{ __('moderation.back_to_queue') }}</a>
        </header>
        @if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif

        @foreach([
            'pending_comments' => ['items' => $comments, 'type' => 'comment', 'public' => false],
            'pending_author_responses' => ['items' => $responses, 'type' => 'response', 'public' => false],
            'pending_continuations' => ['items' => $continuations, 'type' => 'continuation', 'public' => false],
        ] as $heading => $set)
            <section class="participation-section">
                <h2 id="{{ $heading }}">{{ __('moderation.'.$heading) }}</h2>
                @forelse($set['items'] as $item)
                    @php
                        $parent = $item instanceof \App\Models\CommentResponse ? $item->comment->confession : $item->confession;
                        $revision = $item->revisions->first();
                        $targetId = $item->id;
                    @endphp
                    <article class="participation-card">
                        <p>{{ __('moderation.public_parent') }}: <a href="{{ route('confessions.public', ['locale' => $parent->locale, 'publicId' => $parent->public_id]) }}">{{ $parent->title ?: __('moderation.untitled') }}</a></p>
                        @if($item instanceof \App\Models\ConfessionComment && $item->parent?->publicRevision)
                            <blockquote class="comment-context"><strong>{{ __('participation.comment_reply') }}</strong><div class="confession-body">{!! nl2br(e($item->parent->publicRevision->body)) !!}</div></blockquote>
                        @endif
                        @if($item instanceof \App\Models\ConfessionComment)<p>{{ $item->author_alias }}</p>@endif
                        <div class="confession-body">{!! nl2br(e($revision?->body ?? '')) !!}</div>
                        @if($revision)
                            <form method="post" action="{{ route('moderation.participation.decide', ['locale' => request()->route('locale'), 'type' => $set['type'], 'targetId' => $targetId]) }}">
                                @csrf
                                <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                                <input type="hidden" name="expected_lock_version" value="{{ $item->lock_version }}">
                                <input type="hidden" name="expected_parent_lock_version" value="{{ $parent->lock_version }}">
                                <input type="hidden" name="reason_code" value="acceptable">
                                <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                                <button class="button button-primary" type="submit" name="decision" value="approve">{{ __('moderation.approve') }}</button>
                                <button class="button button-secondary" type="submit" name="decision" value="reject">{{ __('moderation.reject') }}</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="muted">—</p>
                @endforelse
                <x-pagination :paginator="$set['items']" :fragment="$heading" />
            </section>
        @endforeach

        @foreach([
            'visible_comments' => ['items' => $visibleComments, 'type' => 'comment'],
            'visible_author_responses' => ['items' => $visibleResponses, 'type' => 'response'],
            'visible_continuations' => ['items' => $visibleContinuations, 'type' => 'continuation'],
        ] as $heading => $set)
            <section class="participation-section">
                <h2 id="{{ $heading }}">{{ __('moderation.'.$heading) }}</h2>
                @forelse($set['items'] as $item)
                    @php
                        $parent = $item instanceof \App\Models\CommentResponse ? $item->comment->confession : $item->confession;
                        $targetId = $item->id;
                    @endphp
                    <article class="participation-card">
                        <p>{{ __('moderation.public_parent') }}: <a href="{{ route('confessions.public', ['locale' => $parent->locale, 'publicId' => $parent->public_id]) }}">{{ $parent->title ?: __('moderation.untitled') }}</a></p>
                        @if($item instanceof \App\Models\ConfessionComment && $item->parent?->publicRevision)
                            <blockquote class="comment-context"><strong>{{ __('participation.comment_reply') }}</strong><div class="confession-body">{!! nl2br(e($item->parent->publicRevision->body)) !!}</div></blockquote>
                        @endif
                        <div class="confession-body">{!! nl2br(e($item->publicRevision->body)) !!}</div>
                        <form method="post" action="{{ route('moderation.participation.decide', ['locale' => request()->route('locale'), 'type' => $set['type'], 'targetId' => $targetId]) }}">
                            @csrf
                            <input type="hidden" name="revision_id" value="{{ $item->public_revision_id }}">
                            <input type="hidden" name="expected_lock_version" value="{{ $item->lock_version }}">
                            <input type="hidden" name="expected_parent_lock_version" value="{{ $parent->lock_version }}">
                            <input type="hidden" name="reason_code" value="privacy">
                            <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                            <button class="button button-secondary" type="submit" name="decision" value="hide">{{ __('moderation.hide_content') }}</button>
                        </form>
                    </article>
                @empty
                    <p class="muted">—</p>
                @endforelse
                <x-pagination :paginator="$set['items']" :fragment="$heading" />
            </section>
        @endforeach
    </section>
@endsection
