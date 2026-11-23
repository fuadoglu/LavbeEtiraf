@extends('layouts.app')

@section('title', __('moderation.staff_published'))

@section('content')
    <x-moderation-shell :title="__('moderation.staff_published')">
        <section class="moderation-compose submission-result" data-clear-confession-draft>
            <p>{{ __('moderation.staff_published_body') }}</p>
            <p class="submission-alias">{{ $confession->author_alias }}</p>
            <div class="moderation-submitted-actions">
                <a class="button button-primary" href="{{ route('confessions.status', ['locale' => request()->route('locale'), 'publicId' => $confession->public_id]) }}">{{ __('guest.write_owner_link') }}</a>
                <a class="button button-secondary" href="{{ route('moderation.queue', ['locale' => request()->route('locale')]) }}">{{ __('moderation.back_to_queue') }}</a>
            </div>
        </section>
    </x-moderation-shell>
@endsection
