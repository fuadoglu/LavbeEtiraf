@props(['selected' => null, 'categories' => []])
@php
    $sort = $sort ?? request()->query('sort', 'new');
    $currentLocale = $lavbeLocale ?? app(App\Support\Lavbe\LocaleRegistry::class)->fallback();
@endphp
<nav class="topic-panel" aria-label="{{ __('lavbe.categories') }}">
    <a class="topic-chip" href="{{ route('home', ['locale' => $currentLocale['segment'], 'sort' => $sort]) }}" @if(!$selected) aria-current="page" @endif>{{ __('lavbe.all_categories') }}</a>
    @foreach($categories as $category)
        <a class="topic-chip" href="{{ route('topic.show', ['locale' => $currentLocale['segment'], 'category' => $category['key'], 'sort' => $sort]) }}" @if($selected === $category['key']) aria-current="page" @endif><x-icon :name="$category['icon']" class="icon-small topic-chip-icon" />{{ $category['label'] }}</a>
    @endforeach
</nav>
