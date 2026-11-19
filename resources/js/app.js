(() => {
    const root = document.documentElement;
    const themeButton = document.querySelector('[data-theme-toggle]');
    const themeColor = document.querySelector('meta[name="theme-color"]');

    if (themeButton) {
        const updateThemeButton = () => {
            const night = root.dataset.theme === 'night';
            themeButton.setAttribute('aria-pressed', String(night));
            themeButton.setAttribute('aria-label', night ? 'Gündüz görünüşünü aktiv et' : 'Gecə görünüşünü aktiv et');
            if (themeColor) themeColor.content = night ? '#181521' : '#faf7f2';
        };

        updateThemeButton();
        themeButton.addEventListener('click', () => {
            const next = root.dataset.theme === 'night' ? 'day' : 'night';
            root.dataset.theme = next;
            updateThemeButton();
            try {
                localStorage.setItem('lavbe-theme', next);
            } catch {
                // Theme selection remains active for this page when storage is unavailable.
            }
        });
    }

    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        const dialog = document.getElementById(button.dataset.dialogOpen);
        if (!dialog || typeof dialog.showModal !== 'function') return;

        button.addEventListener('click', () => dialog.showModal());
        dialog.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    document.querySelectorAll('.topic-panel a').forEach((link) => {
        link.addEventListener('focus', () => {
            link.scrollIntoView({ behavior: 'instant', block: 'nearest', inline: 'nearest' });
        });
    });
})();
