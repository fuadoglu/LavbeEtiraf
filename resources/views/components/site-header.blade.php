<header class="site-header">
    <a class="brand" href="{{ route('home', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" aria-label="Lavbe">
        <span class="brand-mark" aria-hidden="true">L</span>
        <span>Lavbe</span>
    </a>
    <x-site-navigation />
    <div class="header-actions">
        <details class="language-menu">
            <summary class="icon-button language-button" aria-label="{{ $lavbeLocale['native'] }}">
                <x-icon name="languages" />
                <span>{{ strtoupper($lavbeLocale['segment']) }}</span>
                <x-icon name="chevron-down" class="icon-small" />
            </summary>
            <form method="post" action="{{ route('locale.select', ['locale' => $lavbeLocale['segment']]) }}" class="language-picker">
                @csrf
                <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
                <label for="locale-search">{{ __('ui.search_languages') }}</label>
                <input id="locale-search" type="search" data-language-search autocomplete="off">
                <div class="language-options" data-language-options>
                    @foreach(app(App\Support\Lavbe\LocaleRegistry::class)->all() as $language)
                        <button type="submit" name="locale" value="{{ $language['segment'] }}" lang="{{ $language['code'] }}" dir="{{ $language['dir'] }}" data-language-option data-search="{{ mb_strtolower(implode(' ', app(App\Support\Lavbe\LocaleRegistry::class)->searchableNames($language))) }}" @if(app()->getLocale() === $language['code']) aria-current="true" @endif>
                            <span>{{ $language['flag'] }} · {{ $language['native'] }}</span><small>{{ $language['english'] }}</small>
                        </button>
                    @endforeach
                </div>
            </form>
        </details>
        <button class="icon-button theme-button" type="button" data-theme-toggle aria-pressed="false" aria-label="{{ __('ui.night') }}" data-night-label="{{ __('ui.night') }}" data-day-label="{{ __('ui.day') }}">
            <x-icon name="moon" class="theme-icon-night" />
            <x-icon name="sun" class="theme-icon-day" />
        </button>
    </div>
</header>
