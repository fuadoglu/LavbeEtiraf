(() => {
    const root = document.documentElement;
    const languageSearch = document.querySelector('[data-language-search]');
    if (languageSearch) {
        const menu = languageSearch.closest('details');
        menu.addEventListener('toggle', () => {
            if (menu.open) languageSearch.focus();
        });
        menu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                menu.open = false;
                menu.querySelector('summary').focus();
            }
        });
        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target)) menu.open = false;
        });
        const options = [...document.querySelectorAll('[data-language-option]')];
        languageSearch.addEventListener('input', () => {
            const query = languageSearch.value.trim().toLocaleLowerCase();
            options.forEach((option) => {
                option.hidden = query !== '' && !option.dataset.search.toLocaleLowerCase().includes(query);
            });
        });
    }

    const moreButton = document.querySelector('[data-feed-more]');
    const feed = document.querySelector('[data-feed]');
    const feedStatus = document.querySelector('[data-feed-status]');
    if (moreButton && feed && feedStatus) {
        let pending = false;
        let expired = false;
        let retryCursor = feed.dataset.nextCursor;
        const loadMore = async () => {
            if (pending || !retryCursor) return;
            pending = true;
            moreButton.disabled = true;
            feed.setAttribute('aria-busy', 'true');
            try {
                const url = new URL(retryCursor ? feed.dataset.nextUrl || moreButton.dataset.endpoint : moreButton.dataset.endpoint, window.location.href);
                if (!url.searchParams.has('cursor')) url.searchParams.set('cursor', retryCursor);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (response.status === 410) {
                    expired = true;
                    feedStatus.textContent = feedStatus.dataset.refreshLabel;
                    moreButton.hidden = true;
                    feedStatus.hidden = false;
                    return;
                }
                if (!response.ok) throw new Error('Feed request failed');
                const payload = await response.json();
                const existing = new Set([...feed.querySelectorAll('[data-confession-id]')].map((card) => card.dataset.confessionId));
                const fragment = document.createElement('template');
                fragment.innerHTML = payload.html;
                fragment.content.querySelectorAll('[data-confession-id]').forEach((card) => {
                    if (existing.has(card.dataset.confessionId)) card.remove();
                });
                feed.append(fragment.content);
                retryCursor = payload.next_cursor;
                feed.dataset.nextCursor = retryCursor || '';
                feed.dataset.nextUrl = payload.next_url || '';
                moreButton.hidden = !payload.has_more;
                feedStatus.textContent = '';
                feedStatus.hidden = true;
            } catch {
                feedStatus.textContent = feedStatus.dataset.retryLabel;
                feedStatus.hidden = false;
            } finally {
                pending = false;
                moreButton.disabled = false;
                feed.setAttribute('aria-busy', 'false');
            }
        };
        moreButton.addEventListener('click', loadMore);
        feedStatus.addEventListener('click', () => {
            if (pending) return;
            if (expired) {
                window.location.reload();
                return;
            }
            feedStatus.hidden = true;
            loadMore();
        });
    }

    const themeButton = document.querySelector('[data-theme-toggle]');
    const themeColor = document.querySelector('meta[name="theme-color"]');

    if (themeButton) {
        const updateThemeButton = () => {
            const night = root.dataset.theme === 'night';
            themeButton.setAttribute('aria-pressed', String(night));
            themeButton.setAttribute('aria-label', night ? themeButton.dataset.labelDay : themeButton.dataset.labelNight);
            if (themeColor) themeColor.content = night ? '#211d19' : '#f7f3eb';
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
