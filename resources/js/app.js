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
            const controls = draftForm.querySelector('[data-draft-controls]');
            const optIn = controls.querySelector('[data-draft-opt-in]');
            const clear = controls.querySelector('[data-draft-clear]');
            const restore = controls.querySelector('[data-draft-restore]');
            const conflict = controls.querySelector('[data-draft-conflict]');
            const retention = Number(controls.dataset.draftRetentionMs);
            const tabId = Math.random().toString(36).slice(2);
            let saved = JSON.parse(localStorage.getItem(draftKey));
            let knownRevision = saved?.revision ?? null;
            let blocked = false;
            if (saved?.expiresAt && saved.expiresAt <= Date.now()) {
                localStorage.removeItem(draftKey);
                saved = null;
                knownRevision = null;
            }
            const legacy = saved?.version !== 2 ? saved : JSON.parse(sessionStorage.getItem(legacyDraftKey));
            if (saved?.version !== 2) localStorage.removeItem(draftKey);
            sessionStorage.removeItem(legacyDraftKey);
            const emptyForm = !characterInput.value && !title.value;
            if (saved?.version === 2 && saved.expiresAt > Date.now()) {
                optIn.checked = true;
                if (emptyForm) {
                    characterInput.value = saved.body;
                    title.value = saved.title;
                }
            } else {
                if (typeof legacy?.body === 'string' && typeof legacy.title === 'string' && emptyForm) {
                    restore.hidden = false;
                    restore.addEventListener('click', () => {
                        characterInput.value = legacy.body;
                        title.value = legacy.title;
                        optIn.checked = true;
                        restore.hidden = true;
                        saveDraft();
                        characterInput.dispatchEvent(new Event('input'));
                    });
                }
            }
            const saveDraft = (submission = null) => {
                try {
                    if (!optIn.checked || blocked) return;
                    const current = JSON.parse(localStorage.getItem(draftKey));
                    if (current?.version === 2 && current.revision !== knownRevision && current.sourceTab !== tabId) {
                        blocked = true;
                        conflict.hidden = false;
                        return;
                    }
                    const body = characterInput.value;
                    if (body.trim() || title.value.trim()) {
                        const revision = Math.random().toString(36).slice(2);
                        localStorage.setItem(draftKey, JSON.stringify({
                            version: 2, body, title: title.value, submission,
                            expiresAt: Date.now() + retention, sourceTab: tabId, revision,
                        }));
                        knownRevision = revision;
                    } else {
                        localStorage.removeItem(draftKey);
                        knownRevision = null;
                    }
                } catch {
                    // Browser storage may be unavailable or full.
                }
            };
            optIn.addEventListener('change', () => {
                if (optIn.checked) saveDraft();
                else {
                    if (!blocked) localStorage.removeItem(draftKey);
                    knownRevision = null;
                }
            });
            clear.addEventListener('click', () => {
                localStorage.removeItem(draftKey);
                sessionStorage.removeItem(legacyDraftKey);
                sessionStorage.removeItem(pendingDraftKey);
                characterInput.value = '';
                title.value = '';
                optIn.checked = false;
                blocked = false;
                conflict.hidden = true;
                restore.hidden = true;
                knownRevision = null;
                characterInput.dispatchEvent(new Event('input'));
            });
            window.addEventListener?.('storage', (event) => {
                if (event.key === draftKey && optIn.checked && event.newValue !== event.oldValue) {
                    blocked = true;
                    conflict.hidden = false;
                }
            });
            for (const field of [characterInput, title]) {
                field.addEventListener('input', (event) => {
                    if (!event.isComposing) saveDraft();
                });
                field.addEventListener('compositionend', () => saveDraft());
            }
            draftForm.addEventListener('submit', () => {
                if (!optIn.checked || blocked) return;
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
