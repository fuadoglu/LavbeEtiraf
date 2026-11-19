<nav class="primary-nav" aria-label="{{ __('navigation.label') }}">
    <a href="{{ route('home', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" @class(['nav-link', 'is-active' => request()->routeIs('home', 'topic.show')]) @if(request()->routeIs('home', 'topic.show')) aria-current="page" @endif>
        <x-icon name="book-open" />
        <span>{{ __('navigation.read') }}</span>
    </a>
    <a class="nav-link @if(request()->routeIs('confessions.*')) is-active @endif" href="{{ route('confessions.create', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" @if(request()->routeIs('confessions.*')) aria-current="page" @endif>
        <x-icon name="pen-line" />
        <span>{{ __('navigation.write') }}</span>
    </a>
    <a class="nav-link @if(request()->routeIs('account.*')) is-active @endif" href="{{ route('account.home', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}">
        <x-icon name="user-round" />
        <span>{{ __('navigation.mine') }}</span>
    </a>
</nav>
