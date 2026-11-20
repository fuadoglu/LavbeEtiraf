@props(['selected' => null, 'categories' => [], 'variant' => 'mobile'])
@php
    $selectedTopic = collect($categories)->firstWhere('key', $selected);
    $sort = $sort ?? request()->query('sort', 'new');
    $currentLocale = $lavbeLocale ?? app(App\Support\Lavbe\LocaleRegistry::class)->fallback();
@endphp
@if($variant === 'desktop')
    <x-topic-links :selected="$selected" :categories="$categories" :sort="$sort" :current-locale="$currentLocale" />
@else
    <details {{ $attributes->class(['feed-topic-picker']) }}>
        <summary class="feed-topic-trigger"><span>{{ $selectedTopic['label'] ?? __('lavbe.topics_button') }}</span><x-icon name="chevron-down" class="icon-small feed-topic-chevron" /></summary>
        <x-topic-links :selected="$selected" :categories="$categories" :sort="$sort" :current-locale="$currentLocale" />
    </details>
@endif
