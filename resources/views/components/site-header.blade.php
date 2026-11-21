<header class="site-header">
    <a class="brand" href="{{ route('home', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" aria-label="{{ config('app.name') }}">
        <span class="brand-wordmark" aria-hidden="true" style="--brand-logo-mask: url('{{ asset('icons/lavbe-logo.svg') }}'); --brand-heart-mask: url('{{ asset('icons/lavbe-heart.svg') }}')"></span>
    </a>
    <x-site-navigation />
    <div class="header-actions">
        <details class="language-menu">
            <summary class="icon-button language-button" aria-label="{{ $lavbeLocale['native'] }}">
                <img class="language-flag" src="{{ asset('icons/flags/'.strtolower($lavbeLocale['flag']).'.svg') }}" alt="" width="24" height="24">
                <span class="language-current-name">{{ $lavbeLocale['native'] }}</span>
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
                            <img class="language-flag" src="{{ asset('icons/flags/'.strtolower($language['flag']).'.svg') }}" alt="" width="32" height="32">
                            <span class="language-names">
                                <span>{{ $language['native'] }}</span>
                                <small lang="en" dir="ltr">{{ $language['english'] }}</small>
                            </span>
                            @if(app()->getLocale() === $language['code'])<x-icon name="circle-check" class="icon-small language-selected" />@endif
                        </button>
                    @endforeach
                    <p data-language-empty hidden role="status" aria-live="polite">{{ __('ui.language_no_match') }}</p>
                </div>
            </form>
        </details>
        <a class="icon-button header-search" href="{{ route('search', ['locale' => $lavbeLocale['segment']]) }}" aria-label="{{ __('lavbe.search_title') }}" title="{{ __('lavbe.search_title') }}" @if(request()->routeIs('search')) aria-current="page" @endif><x-icon name="search" /></a>
        <a class="icon-button header-supported" href="{{ route('confessions.supported', ['locale' => $lavbeLocale['segment']]) }}" aria-label="{{ __('participation.supported_heading') }}" title="{{ __('participation.supported_heading') }}"><x-icon name="heart" /></a>
        <button class="icon-button theme-button" type="button" data-theme-toggle aria-pressed="false" data-label-night="{{ __('navigation.theme_night') }}" data-label-day="{{ __('navigation.theme_day') }}" aria-label="{{ __('navigation.theme_night') }}" >
            <x-icon name="moon" class="theme-icon-night" />
            <x-icon name="sun" class="theme-icon-day" />
        </button>
    </div>
</header>
