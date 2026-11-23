@props(['title'])

@php
    $locale = request()->route('locale');
    $route = request()->route()?->getName() ?? '';
    $isAdmin = auth()->user()?->moderatorGrants()
        ->where('role', 'admin')->where('locale', '*')->where('is_active', true)->whereNull('revoked_at')->exists() ?? false;
    $sections = [
        ['route' => 'moderation.queue', 'active' => ['moderation.queue', 'moderation.show'], 'label' => __('moderation.queue_title'), 'icon' => 'book-open'],
        ['route' => 'moderation.participation', 'active' => ['moderation.participation'], 'label' => __('moderation.participation_queue'), 'icon' => 'message-circle'],
        ['route' => 'moderation.reports', 'active' => ['moderation.reports', 'moderation.reports.show'], 'label' => __('moderation.reports_queue'), 'icon' => 'triangle-alert'],
        ['route' => 'moderation.appeals', 'active' => ['moderation.appeals', 'moderation.appeals.show'], 'label' => __('moderation.appeals_queue'), 'icon' => 'route'],
    ];
    if ($isAdmin) {
        $sections[] = ['route' => 'moderation.grants', 'active' => ['moderation.grants', 'moderation.grants.search'], 'label' => __('moderation.grant_title'), 'icon' => 'users-round'];
    }
    $currentSection = collect($sections)->first(fn ($section) => in_array($route, $section['active'], true)) ?? $sections[0];
@endphp

<div class="moderation-workspace">
    <aside class="moderation-sidebar">
        <nav class="moderation-navigation" aria-label="{{ __('moderation.queue_title') }}">
            @foreach($sections as $section)
                <a href="{{ route($section['route'], ['locale' => $locale]) }}" @if(in_array($route, $section['active'], true)) aria-current="page" @endif>
                    <x-icon :name="$section['icon']" class="icon-small" />
                    <span>{{ $section['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <details class="moderation-mobile-nav">
            <summary><x-icon :name="$currentSection['icon']" class="icon-small" /><span>{{ $currentSection['label'] }}</span><x-icon name="chevron-down" class="icon-small" /></summary>
            <nav aria-label="{{ __('moderation.queue_title') }}">
                @foreach($sections as $section)
                    <a href="{{ route($section['route'], ['locale' => $locale]) }}" @if(in_array($route, $section['active'], true)) aria-current="page" @endif>
                        <x-icon :name="$section['icon']" class="icon-small" />{{ $section['label'] }}
                    </a>
                @endforeach
            </nav>
        </details>
        <form method="post" action="{{ route('moderation.logout', ['locale' => $locale]) }}" class="moderation-logout">
            @csrf
            <button type="submit" aria-label="{{ __('moderation.logout') }}"><x-icon name="arrow-left" class="icon-small" /><span>{{ __('moderation.logout') }}</span></button>
        </form>
    </aside>
    <section class="moderation-main" aria-labelledby="moderation-page-title">
        <header class="moderation-heading">
            <h1 id="moderation-page-title">{{ $title }}</h1>
        </header>
        @if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
        {{ $slot }}
    </section>
</div>
