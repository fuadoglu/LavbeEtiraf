(() => {
    const root = document.documentElement;
    const consentDialog = document.querySelector('[data-consent-dialog]');
    const consentForm = consentDialog?.querySelector('[data-consent-form]');
    const analyticsConsent = consentDialog?.querySelector('[data-analytics-consent]');
    const consentKey = 'lavbe:consent:v1';
    const analyticsId = document.querySelector('meta[name="lavbe-analytics-id"]')?.content || '';
    const analyticsContextMeta = document.querySelector('meta[name="lavbe-analytics-context"]')?.content || '';
    let analyticsLoaded = false;
    let activeAnalyticsContext = null;
    const readConsent = () => {
        try {
            const value = JSON.parse(localStorage.getItem(consentKey) || 'null');
            return value?.version === 1 && typeof value.analytics === 'boolean' && Number.isFinite(value.expires) && value.expires > Date.now() ? value : null;
        } catch { return null; }
    };
    let analyticsActive = false;
    const disableAnalytics = () => {
        analyticsActive = false;
        if (!analyticsId) return;
        window['ga-disable-' + analyticsId] = true;
        if (analyticsLoaded && window.gtag) window.gtag('consent', 'update', { analytics_storage: 'denied' });
    };
    const enableAnalytics = () => {
        if (analyticsActive || !/^G-[A-Z0-9]+$/.test(analyticsId) || !analyticsContextMeta) return;
        if (analyticsLoaded && window.gtag) {
            window['ga-disable-' + analyticsId] = false;
            window.gtag('consent', 'update', { analytics_storage: 'granted' });
            analyticsActive = true;
            return;
        }
        let context;
        try { context = JSON.parse(atob(analyticsContextMeta)); } catch { return; }
        if (!context || !['feed', 'topic', 'confession'].includes(context.type) || context.locale === 'az' || !/^\/[a-z]{2}(?:-[a-z]{2})?\/(?:$|topics$|confession$)/.test(context.path)) return;
        activeAnalyticsContext = context;
        analyticsActive = true;
        analyticsLoaded = true;
        window['ga-disable-' + analyticsId] = false;
        window.dataLayer = window.dataLayer || [];
        const gtag = (...args) => window.dataLayer.push(args);
        window.gtag = gtag;
        gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'granted' });
        gtag('js', new Date());
        gtag('config', analyticsId, {
            send_page_view: false,
            page_location: `${window.location.origin}${context.path}`,
            page_title: context.title,
            page_referrer: '',
            allow_google_signals: false,
            allow_ad_personalization_signals: false,
        });
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(analyticsId)}`;
        document.head.append(script);
        gtag('event', 'page_view', { content_locale: context.locale, page_type: context.type });
    };
    if (analyticsId) disableAnalytics();
    const initialConsent = readConsent();
    if (initialConsent?.analytics) enableAnalytics();
    if (!initialConsent && consentDialog && typeof consentDialog.showModal === 'function') consentDialog.showModal();
    document.querySelector('[data-consent-open]')?.addEventListener('click', () => {
        const current = readConsent();
        if (analyticsConsent) analyticsConsent.checked = Boolean(current?.analytics);
        consentDialog?.showModal();
    });
    consentForm?.addEventListener('submit', (event) => {
        if (event.submitter?.value !== 'save') return;
        event.preventDefault();
        try { localStorage.setItem(consentKey, JSON.stringify({ version: 1, analytics: Boolean(analyticsConsent?.checked), expires: Date.now() + 180 * 24 * 60 * 60 * 1000 })); } catch {}
        if (analyticsConsent?.checked) enableAnalytics();
        else disableAnalytics();
        consentDialog.close();
    });
    consentDialog?.querySelector('[data-consent-decline]')?.addEventListener('click', () => {
        try { localStorage.setItem(consentKey, JSON.stringify({ version: 1, analytics: false, expires: Date.now() + 180 * 24 * 60 * 60 * 1000 })); } catch {}
        disableAnalytics();
        consentDialog.close();
    });
    const deferNavigationForAnalytics = (eventName, parameters, navigate) => {
        if (!analyticsActive || !window.gtag || !activeAnalyticsContext) return false;
        let completed = false;
        const complete = () => {
            if (completed) return;
            completed = true;
            navigate();
        };
        const fallback = window.setTimeout(complete, 700);
        window.gtag('event', eventName, {
            content_locale: activeAnalyticsContext.locale,
            page_type: activeAnalyticsContext.type,
            ...parameters,
            event_timeout: 600,
            event_callback: () => {
                window.clearTimeout(fallback);
                complete();
            },
        });
        return true;
    };
    document.querySelectorAll('[data-next-confession]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (deferNavigationForAnalytics('next_confession', {}, () => window.location.assign(link.href))) event.preventDefault();
        });
    });
    document.querySelectorAll('[data-language-option]').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!button.form || !button.value) return;
            const targetLocale = button.value;
            if (deferNavigationForAnalytics('locale_change', { target_locale: targetLocale }, () => button.form.requestSubmit(button))) event.preventDefault();
        });
    });
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
