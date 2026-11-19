<nav class="primary-nav" aria-label="{{ __('ui.nav') }}">
    <a href="{{ route('home', ['locale' => $lavbeLocale['segment']]) }}" @class(['nav-link', 'is-active' => request()->routeIs('home', 'topic.show')]) @if(request()->routeIs('home', 'topic.show')) aria-current="page" @endif>
        <x-icon name="book-open" />
        <span>{{ __('ui.read') }}</span>
    </a>
    <a class="nav-link @if(request()->routeIs('confessions.*')) is-active @endif" href="{{ route('confessions.create', ['locale' => $lavbeLocale['segment']]) }}" @if(request()->routeIs('confessions.*')) aria-current="page" @endif>
        <x-icon name="pen-line" />
        <span>{{ __('ui.write') }}</span>
    </a>
    <a class="nav-link @if(request()->routeIs('account.*')) is-active @endif" href="{{ route('account.home', ['locale' => $lavbeLocale['segment']]) }}">
        <x-icon name="user-round" />
        <span>{{ __('ui.mine') }}</span>
    </a>
</nav>
