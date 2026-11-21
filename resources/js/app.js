(() => {
    const root = document.documentElement;
    if (window.isSecureContext && 'serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {}));
    }

    const characterInput = document.querySelector('[data-character-count-input]');
    const characterCount = document.querySelector('[data-character-count]');
    const draftForm = characterInput?.closest?.('[data-confession-draft]');
    const draftKey = 'lavbe-confession-draft';
    const legacyDraftKey = 'lavbe-tab-draft';
    const pendingDraftKey = 'lavbe-pending-confession-draft';
    try {
        if (document.querySelector('[data-clear-confession-draft]')) {
            const pending = sessionStorage.getItem(pendingDraftKey);
            const saved = JSON.parse(localStorage.getItem(draftKey));
            if (pending && saved?.submission === pending) localStorage.removeItem(draftKey);
            sessionStorage.removeItem(pendingDraftKey);
            sessionStorage.removeItem(legacyDraftKey);
        } else if (draftForm) {
            const title = draftForm.elements.namedItem('title');
            let saved = JSON.parse(localStorage.getItem(draftKey));
            if (!saved) {
                saved = JSON.parse(sessionStorage.getItem(legacyDraftKey));
                if (saved) localStorage.setItem(draftKey, JSON.stringify(saved));
            }
            sessionStorage.removeItem(legacyDraftKey);
            if (!characterInput.value && !title.value && typeof saved?.body === 'string' && typeof saved.title === 'string') {
                characterInput.value = saved.body;
                title.value = saved.title;
            }
            const saveDraft = (submission = null) => {
                try {
                    const body = characterInput.value;
                    if (body.trim() || title.value.trim()) {
                        localStorage.setItem(draftKey, JSON.stringify({ body, title: title.value, submission }));
                    } else {
                        localStorage.removeItem(draftKey);
                    }
                } catch {
                    // Browser storage may be unavailable or full.
                }
            };
            for (const field of [characterInput, title]) {
                field.addEventListener('input', (event) => {
                    if (!event.isComposing) saveDraft();
                });
                field.addEventListener('compositionend', () => saveDraft());
            }
            draftForm.addEventListener('submit', () => {
                const submission = draftForm.elements.namedItem('idempotency_key').value;
                saveDraft(submission);
                try {
                    sessionStorage.setItem(pendingDraftKey, submission);
                } catch {
                    // Keep the draft if the submission cannot be matched.
                }
            });
        }
    } catch {
        // The form remains usable if browser storage cannot be read.
    }
    if (characterInput && characterCount) {
        const maximum = Number(characterCount.dataset.max);
        const updateRemaining = (event) => {
            if (event?.isComposing) return;
            const characters = [...characterInput.value];
            if (characters.length > maximum) {
                characterInput.value = characters.slice(0, maximum).join('');
            }
            characterCount.value = String(Math.max(0, maximum - [...characterInput.value].length));
        };
        updateRemaining();
        characterInput.addEventListener('input', updateRemaining);
        characterInput.addEventListener('compositionend', () => updateRemaining());
        window.addEventListener?.('pageshow', () => updateRemaining());
    }

    const languageSearch = document.querySelector('[data-language-search]');
    if (languageSearch) {
        const menu = languageSearch.closest('details');
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
        const noMatches = menu.querySelector('[data-language-empty]');
        function filterLanguages() {
            const query = languageSearch.value.trim().toLocaleLowerCase();
            options.forEach((option) => {
                option.hidden = query !== '' && !option.dataset.search.toLocaleLowerCase().includes(query);
            });
            noMatches.hidden = !options.every((option) => option.hidden);
        }
        languageSearch.addEventListener('input', filterLanguages);
        menu.addEventListener('toggle', () => {
            if (menu.open) {
                languageSearch.focus();
            } else {
                languageSearch.value = '';
                filterLanguages();
            }
        });
        window.addEventListener?.('pageshow', (event) => {
            if (event.persisted) menu.open = false;
        });
        languageSearch.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || event.isComposing) return;
            event.preventDefault();
            const matches = options.filter((option) => !option.hidden);
            if (matches.length === 1) matches[0].click();
            else matches[0]?.focus();
        });
    }

    const themeButton = document.querySelector('[data-theme-toggle]');
    const themeColor = document.querySelector('meta[name="theme-color"]');

    if (themeButton) {
        const updateThemeButton = () => {
            const night = root.dataset.theme === 'night';
            themeButton.setAttribute('aria-pressed', String(night));
            themeButton.setAttribute('aria-label', night ? themeButton.dataset.labelDay : themeButton.dataset.labelNight);
            if (themeColor) themeColor.content = night ? themeColor.dataset.night : themeColor.dataset.day;
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

        if (!dialog.dataset.dialogFocusReturnBound) {
            dialog.dataset.dialogFocusReturnBound = 'true';
            dialog.addEventListener('close', () => {
                if (dialog._suppressDialogFocusReturn) {
                    dialog._suppressDialogFocusReturn = false;
                    return;
                }
                dialog._dialogReturnFocus?.focus?.();
                dialog._dialogReturnFocus = null;
            });
        }

        button.addEventListener('click', (event) => {
            event.preventDefault();
            const currentDialog = button.closest('dialog[open]');
            if (currentDialog === dialog) return;
            const returnFocus = currentDialog?._dialogReturnFocus || button;
            if (currentDialog) {
                currentDialog._suppressDialogFocusReturn = true;
                currentDialog.close();
            }
            dialog._dialogReturnFocus = returnFocus;
            dialog.showModal();
        });
        dialog.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    document.querySelectorAll('[data-share-button]').forEach((button) => {
        const control = button.closest('[data-share-control]');
        const status = control?.querySelector('[data-share-status]');
        const fallback = control?.querySelector('[data-share-fallback]');
        const urlField = control?.querySelector('[data-share-url-field]');
        if (!status || !fallback || !urlField) return;

        button.addEventListener('click', async () => {
            const shareData = { title: button.dataset.shareTitle, url: button.dataset.shareUrl };
            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                    return;
                }
                if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
                await navigator.clipboard.writeText(shareData.url);
                status.textContent = button.dataset.copiedLabel;
                return;
            } catch (error) {
                if (error?.name === 'AbortError') return;
                if (navigator.share && navigator.clipboard?.writeText) {
                    try {
                        await navigator.clipboard.writeText(shareData.url);
                        status.textContent = button.dataset.copiedLabel;
                        return;
                    } catch {
                        // The selectable URL remains available below.
                    }
                }
            }
            urlField.value = shareData.url;
            fallback.hidden = false;
            status.textContent = button.dataset.copyHint;
            urlField.focus();
            urlField.select();
        });
    });

    document.querySelectorAll('.topic-panel a').forEach((link) => {
        link.addEventListener('focus', () => {
            link.scrollIntoView({ behavior: 'instant', block: 'nearest', inline: 'nearest' });
        });
    });
})();
