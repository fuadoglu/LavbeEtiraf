@props(['selected' => null, 'categories' => [], 'sort' => 'new', 'currentLocale' => null])
@php($currentLocale = $currentLocale ?? ($lavbeLocale ?? app(App\Support\Lavbe\LocaleRegistry::class)->fallback()))
<nav class="topic-panel" aria-label="{{ __('lavbe.categories') }}">
    <a class="topic-chip" href="{{ route('home', ['locale' => $currentLocale['segment'], 'sort' => $sort]) }}" @if(!$selected) aria-current="page" @endif><span>{{ __('lavbe.all_categories') }}</span></a>
    @foreach($categories as $category)
        <a class="topic-chip" href="{{ route('topic.show', ['locale' => $currentLocale['segment'], 'category' => $category['key'], 'sort' => $sort]) }}" @if($selected === $category['key']) aria-current="page" @endif><x-icon :name="$category['icon']" class="icon-small topic-chip-icon" /><span>{{ $category['label'] }}</span></a>
    @endforeach
</nav>
