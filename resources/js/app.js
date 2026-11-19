(() => {
    const root = document.documentElement;
    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).then((registration) => {
            const cacheOfflinePage = (worker) => {
                if (worker?.state === 'activated') {
                    worker.postMessage({ type: 'CACHE_OFFLINE_PAGE', locale: root.dataset.locale });
                    return;
                }
                worker?.addEventListener('statechange', () => cacheOfflinePage(worker), { once: true });
            };
            cacheOfflinePage(registration.active || registration.waiting || registration.installing);
        }).catch(() => {});
    }

    const installButton = document.querySelector('[data-pwa-install]');
    const installHelp = document.querySelector('[data-pwa-install-help]');
    let installPrompt = null;
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        if (installButton) installButton.hidden = false;
    });
    if (installHelp && /iPhone|iPad|iPod/i.test(navigator.userAgent) && !('standalone' in navigator && navigator.standalone)) {
        installHelp.hidden = false;
    }
    installButton?.addEventListener('click', async () => {
        if (!installPrompt) return;
        await installPrompt.prompt();
        await installPrompt.userChoice;
        installPrompt = null;
        installButton.hidden = true;
    });

    const pushControl = document.querySelector('[data-push-control]');
    if (pushControl) {
        const button = pushControl.querySelector('[data-push-toggle]');
        const status = pushControl.querySelector('[data-push-status]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const vapidKey = pushControl.dataset.vapidKey;
        const encodeVapidKey = (key) => {
            const padded = `${key}${'='.repeat((4 - key.length % 4) % 4)}`;
            const binary = atob(padded.replace(/-/g, '+').replace(/_/g, '/'));
            return Uint8Array.from(binary, (character) => character.charCodeAt(0));
        };
        const sendSubscription = async (url, method, subscription) => {
            const response = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ endpoint: subscription.endpoint, keys: subscription.toJSON().keys }),
            });
            if (!response.ok) throw new Error('Push preference update failed');
            return response.status === 204 ? null : response.json();
        };
        const setState = (enabled, message) => {
            button.setAttribute('aria-pressed', String(enabled));
            status.textContent = message;
        };
        const loadState = async () => {
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                button.disabled = true;
                setState(false, pushControl.dataset.unavailableLabel);
                return { registration: null, subscription: null, active: false };
            }
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                setState(false, pushControl.dataset.disabledLabel);
                return { registration, subscription, active: false };
            }
            const response = await sendSubscription(pushControl.dataset.statusUrl, 'POST', subscription);
            setState(Boolean(response?.active), response?.active ? pushControl.dataset.enabledLabel : pushControl.dataset.disabledLabel);
            return { registration, subscription, active: Boolean(response?.active) };
        };

        loadState().catch(() => {
            button.disabled = true;
            setState(false, pushControl.dataset.unavailableLabel);
        });

        button.addEventListener('click', async () => {
            button.disabled = true;
            try {
                let state = await loadState();
                if (!state.registration) return;
                if (state.active && state.subscription) {
                    await sendSubscription(pushControl.dataset.unsubscribeUrl, 'DELETE', state.subscription);
                    await state.subscription.unsubscribe();
                    setState(false, pushControl.dataset.disabledLabel);
                    return;
                }
                if (Notification.permission !== 'granted') {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        setState(false, pushControl.dataset.deniedLabel);
                        return;
                    }
                }
                const subscription = state.subscription || await state.registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: encodeVapidKey(vapidKey),
                });
                await sendSubscription(pushControl.dataset.subscribeUrl, 'POST', subscription);
                setState(true, pushControl.dataset.enabledLabel);
            } catch {
                setState(false, pushControl.dataset.unavailableLabel);
            } finally {
                button.disabled = false;
            }
        });
    }

    const submitted = document.querySelector('[data-confession-submit-success]');
    if (submitted) {
        try {
            localStorage.removeItem(`lavbe:draft:v1:${submitted.dataset.draftLocale}`);
        } catch {
            // A successful submission stays successful if device storage is unavailable.
        }
    }

    const draftForm = document.querySelector('[data-offline-draft-form]');
    if (draftForm) {
        const consent = draftForm.querySelector('[data-draft-consent]');
        const status = draftForm.querySelector('[data-draft-status]');
        const locale = draftForm.dataset.draftLocale;
        const key = `lavbe:draft:v1:${locale}`;
        const fields = ['title', 'body', 'category_key', 'response_expectation'];
        const showStatus = (message) => {
            status.textContent = message;
            status.hidden = false;
        };
        const readFields = () => Object.fromEntries(fields.map((name) => [name, draftForm.elements.namedItem(name)?.value ?? '']));
        const storeDraft = () => {
            if (!consent.checked) return;
            try {
                localStorage.setItem(key, JSON.stringify({ expires: Date.now() + 24 * 60 * 60 * 1000, fields: readFields() }));
            } catch {
                showStatus(draftForm.dataset.storageError);
                return;
            }
            showStatus(draftForm.dataset.draftSaved);
        };

        if (draftForm.dataset.hasOldInput !== 'true') {
            try {
                const saved = JSON.parse(localStorage.getItem(key) || 'null');
                if (saved && Number.isFinite(saved.expires) && saved.expires > Date.now() && saved.fields) {
                    consent.checked = true;
                    fields.forEach((name) => {
                        const field = draftForm.elements.namedItem(name);
                        if (field && typeof saved.fields[name] === 'string') field.value = saved.fields[name];
                    });
                } else if (saved) {
                    localStorage.removeItem(key);
                    showStatus(draftForm.dataset.draftExpired);
                }
            } catch {
                showStatus(draftForm.dataset.storageError);
            }
        }

        draftForm.addEventListener('input', storeDraft);
        draftForm.addEventListener('change', (event) => {
            if (event.target === consent) {
                if (consent.checked) storeDraft();
                else {
                    try { localStorage.removeItem(key); } catch { showStatus(draftForm.dataset.storageError); }
                }
            } else {
                storeDraft();
            }
        });
        draftForm.addEventListener('submit', (event) => {
            if (!navigator.onLine) {
                event.preventDefault();
                storeDraft();
                showStatus(draftForm.dataset.networkError);
            }
        });
    }
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
            if (themeColor) themeColor.content = themeColor.dataset[night ? 'night' : 'day'];
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
