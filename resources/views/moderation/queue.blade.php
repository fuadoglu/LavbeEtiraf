@extends('layouts.app')

@section('title', __('moderation.queue_title'))

@section('content')
    <x-moderation-shell :title="__('moderation.queue_title')">
        <a class="button button-primary moderation-create-link" href="{{ route('moderation.confessions.create', ['locale' => request()->route('locale')]) }}"><x-icon name="pen-line" class="icon-small" />{{ __('guest.write_title') }}</a>
        @if($allowed->count() > 1)
            <nav class="moderation-filter" aria-label="{{ __('moderation.target_language') }}">
                <a href="{{ route('moderation.queue', ['locale' => request()->route('locale'), 'state' => $selectedState]) }}" @if(! $selected) aria-current="page" @endif>{{ __('moderation.all_languages') }}</a>
                @foreach($allowed as $language)
                    <a href="{{ route('moderation.queue', ['locale' => request()->route('locale'), 'language' => $language, 'state' => $selectedState]) }}" @if($selected === $language) aria-current="page" @endif>{{ $language }}</a>
                @endforeach
            </nav>
        @endif
        <nav class="moderation-filter moderation-filter--states" aria-label="{{ __('moderation.state') }}">
            @foreach(['review' => 'state_review', 'public' => 'state_public', 'hidden' => 'state_hidden'] as $state => $label)
                <a href="{{ route('moderation.queue', ['locale' => request()->route('locale'), 'language' => $selected ?: null, 'state' => $state]) }}" @if($selectedState === $state) aria-current="page" @endif>{{ __('moderation.'.$label) }}</a>
            @endforeach
        </nav>
        @if($allowed->isEmpty() && $isAdmin)
            <x-status>{{ __('moderation.grant_admin_locale') }}</x-status>
        @endif
        <div class="moderation-list">
            @forelse($items as $item)
                @php($category = $item->category_key ? app(\App\Support\Lavbe\LocaleRegistry::class)->category($item->category_key) : null)
                <article class="moderation-card">
                    <div class="moderation-card-meta">
                        <span class="moderation-pill">{{ strtoupper($item->locale) }}</span>
                        <span>{{ $category['labels'][app()->getLocale()] ?? $category['labels']['en'] ?? '—' }}</span>
                        <time datetime="{{ $item->created_at }}">{{ \Illuminate\Support\Carbon::parse($item->created_at)->translatedFormat('d M Y') }}</time>
                    </div>
                    <div class="moderation-card-main">
                        <h2>{{ $item->title ?: __('moderation.untitled') }}</h2>
                        <a class="moderation-open" href="{{ route('moderation.show', ['locale' => request()->route('locale'), 'confessionId' => $item->id, 'revision_id' => $selectedState === 'review' ? null : $item->revision_id]) }}">{{ __('moderation.open_review') }} <x-icon name="arrow-left" class="icon-small" /></a>
                    </div>
                </article>
            @empty
                <div class="moderation-empty"><x-icon name="circle-check" /><p>{{ __('moderation.queue_empty') }}</p></div>
            @endforelse
        </div>
        <x-pagination :paginator="$items" />
    </x-moderation-shell>
@endsection
