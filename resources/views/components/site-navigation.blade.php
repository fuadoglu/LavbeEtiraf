<nav class="primary-nav" aria-label="{{ app()->getLocale() === 'en' ? 'Main navigation' : 'Əsas naviqasiya' }}">
    <a href="{{ route('home', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" @class(['nav-link', 'is-active' => request()->routeIs('home', 'topic.show')]) @if(request()->routeIs('home', 'topic.show')) aria-current="page" @endif>
        <x-icon name="book-open" />
        <span>Oxu</span>
    </a>
    <a class="nav-link @if(request()->routeIs('confessions.*')) is-active @endif" href="{{ route('confessions.create', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" @if(request()->routeIs('confessions.*')) aria-current="page" @endif>
        <x-icon name="pen-line" />
        <span>Yaz</span>
    </a>
    <span class="nav-link" aria-disabled="true" title="Şəxsi bölmə növbəti mərhələdə əlavə olunur">
        <x-icon name="user-round" />
        <span>Mənim</span>
    </span>
</nav>
