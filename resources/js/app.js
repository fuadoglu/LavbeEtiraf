(() => {
    const root = document.documentElement;
    const languageSearch = document.querySelector('[data-language-search]');
    if (languageSearch) {
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
        let retryCursor = feed.dataset.nextCursor;
        const loadMore = async () => {
            if (pending || !retryCursor) return;
            pending = true;
            moreButton.disabled = true;
            try {
                const url = new URL(retryCursor ? feed.dataset.nextUrl || moreButton.dataset.endpoint : moreButton.dataset.endpoint, window.location.href);
                if (!url.searchParams.has('cursor')) url.searchParams.set('cursor', retryCursor);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (response.status === 410) {
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
            }
        };
        moreButton.addEventListener('click', loadMore);
        feedStatus.addEventListener('click', () => {
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
