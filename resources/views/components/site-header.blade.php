<header class="site-header">
    <a class="brand" href="{{ route('home') }}" aria-label="Lavbe · Ana səhifə">
        <span class="brand-mark" aria-hidden="true">L</span>
        <span>Lavbe</span>
    </a>
    <x-site-navigation />
    <div class="header-actions">
        <span class="icon-button language-button" aria-label="Hazırkı dil: Azərbaycan dili">
            <x-icon name="languages" />
            <span>AZ</span>
            <x-icon name="chevron-down" class="icon-small" />
        </span>
        <button class="icon-button theme-button" type="button" data-theme-toggle aria-pressed="false" aria-label="Gecə görünüşünü aktiv et">
            <x-icon name="moon" class="theme-icon-night" />
            <x-icon name="sun" class="theme-icon-day" />
        </button>
    </div>
</header>
