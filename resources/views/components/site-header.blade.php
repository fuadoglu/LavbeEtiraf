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
                <label for="locale-search">{{ __('navigation.language_search') }}</label>
                <input id="locale-search" type="search" data-language-search autocomplete="off">
                <div class="language-options" data-language-options>
                    @foreach(app(App\Support\Lavbe\LocaleRegistry::class)->all() as $language)
                        <button type="submit" name="locale" value="{{ $language['segment'] }}" lang="{{ $language['code'] }}" dir="{{ $language['dir'] }}" data-language-option data-search="{{ mb_strtolower(implode(' ', app(App\Support\Lavbe\LocaleRegistry::class)->searchableNames($language))) }}" @if(app()->getLocale() === $language['code']) aria-current="true" @endif>
                            <img class="language-flag" src="{{ asset('icons/flags/'.strtolower($language['flag']).'.svg') }}" width="28" height="28" alt="" loading="lazy">
                            <span class="language-names">{{ $language['native'] }}<small lang="en">{{ $language['english'] }}</small></span>
                            @if(app()->getLocale() === $language['code'])<x-icon name="circle-check" class="icon-small" />@endif
                        </button>
                    @endforeach
                </div>
            </form>
        </details>
        <button class="icon-button theme-button" type="button" data-theme-toggle aria-pressed="false" data-label-night="{{ __('navigation.theme_night') }}" data-label-day="{{ __('navigation.theme_day') }}" aria-label="{{ __('navigation.theme_night') }}" >
            <x-icon name="moon" class="theme-icon-night" />
            <x-icon name="sun" class="theme-icon-day" />
        </button>
    </div>
</header>
