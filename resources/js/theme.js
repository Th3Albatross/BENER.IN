/**
 * BENER.IN: appearance mode.
 *
 * The chosen mode lives in localStorage, not the database: it is a per-device
 * display preference, so keeping it client-side avoids a write on every change
 * and works on the login page before a session exists.
 *
 * The mode is applied to <html data-theme> as early as possible to avoid a
 * flash of the wrong theme. `layouts/app.blade.php` and the login page include
 * a tiny inline bootstrap that runs before paint; this module handles the
 * Settings-page selector and keeps multiple tabs in sync.
 */
(() => {
    const STORAGE_KEY = 'benerin:theme';
    const THEMES = ['light', 'dim', 'dark'];
    const DEFAULT_THEME = 'light';

    const root = document.documentElement;

    const isTheme = (value) => THEMES.includes(value);

    const readStoredTheme = () => {
        try {
            const stored = window.localStorage.getItem(STORAGE_KEY);
            return isTheme(stored) ? stored : null;
        } catch {
            // Storage can throw when cookies/site data are blocked.
            return null;
        }
    };

    const storeTheme = (theme) => {
        try {
            window.localStorage.setItem(STORAGE_KEY, theme);
        } catch {
            // A blocked storage API must not break the page.
        }
    };

    const clearTheme = () => {
        try {
            window.localStorage.removeItem(STORAGE_KEY);
        } catch {
            // Nothing to clear if storage is unavailable.
        }
    };

    const applyTheme = (theme) => {
        root.setAttribute('data-theme', theme);
        root.dataset.themeSource = 'user';
    };

    /** The stored choice, else the OS preference, else the default. */
    const resolveInitialTheme = () => {
        const stored = readStoredTheme();
        if (stored) return stored;

        if (window.matchMedia?.('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }

        return DEFAULT_THEME;
    };

    // Adopt the stored mode on load in case the inline bootstrap used a fallback.
    const stored = readStoredTheme();
    if (stored) {
        applyTheme(stored);
    }

    // Keep other tabs of the same site in sync.
    window.addEventListener('storage', (event) => {
        if (event.key !== STORAGE_KEY) return;

        const next = isTheme(event.newValue) ? event.newValue : resolveInitialTheme();
        applyTheme(next);
        syncSelector(next);
    });

    /** Reflect the active mode in the Settings selector, if present. */
    function syncSelector(theme) {
        document.querySelectorAll('.settings-theme-input').forEach((input) => {
            input.checked = input.value === theme;
        });
    }

    const initSelector = () => {
        const inputs = document.querySelectorAll('.settings-theme-input');
        if (!inputs.length) return;

        const note = document.querySelector('[data-theme-note]');

        syncSelector(root.getAttribute('data-theme') || resolveInitialTheme());

        inputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!input.checked || !isTheme(input.value)) return;

                applyTheme(input.value);
                storeTheme(input.value);

                // Confirm the change without a page reload.
                if (note) {
                    note.hidden = false;
                    window.clearTimeout(note.dataset.timeoutId);
                    note.dataset.timeoutId = window.setTimeout(() => {
                        note.hidden = true;
                    }, 4000);
                }
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSelector);
    } else {
        initSelector();
    }

    /*
     * Signing out clears the stored mode so a shared computer does not hand the
     * next person the previous user's appearance. The link still navigates; we
     * only drop the key first.
     */
    const initLogoutReset = () => {
        document.querySelectorAll('[data-theme-reset]').forEach((link) => {
            link.addEventListener('click', clearTheme);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLogoutReset);
    } else {
        initLogoutReset();
    }
})();
