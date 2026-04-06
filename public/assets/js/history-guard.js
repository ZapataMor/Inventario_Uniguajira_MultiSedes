(() => {
    const AUTH_PATH_PREFIXES = [
        '/login',
        '/register',
        '/password',
        '/forgot-password',
        '/reset-password',
    ];

    const toPath = (value) => {
        try {
            const resolved = new URL(String(value || '/'), window.location.origin);
            return `${resolved.pathname}${resolved.search}`;
        } catch (error) {
            const fallback = String(value || '/').replace(window.location.origin, '');
            return fallback || '/';
        }
    };

    const toPathname = (value) => {
        try {
            return new URL(String(value || '/'), window.location.origin).pathname;
        } catch (error) {
            return String(value || '/');
        }
    };

    const isAuthPath = (value) => {
        const pathname = toPathname(value);
        return AUTH_PATH_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`));
    };

    const isAuthDocument = () => document.body?.dataset?.authPage === '1';
    const shouldLockAuthBack = () => document.body?.dataset?.authLock === '1';

    const isFullHtmlDocument = (html) => {
        const normalized = String(html ?? '').trimStart().toLowerCase();
        return normalized.startsWith('<!doctype html') || normalized.startsWith('<html');
    };

    const navigateHard = (targetUrl, replace = false) => {
        if (replace) {
            window.location.replace(targetUrl);
            return;
        }

        window.location.assign(targetUrl);
    };

    const authFallbackUrl = isAuthDocument() ? toPath(window.location.href) : null;
    const forceAuthFallback = () => {
        if (!authFallbackUrl) {
            return;
        }

        if (toPath(window.location.href) !== authFallbackUrl) {
            window.location.replace(authFallbackUrl);
        }
    };

    const primeAuthBackLock = () => {
        if (!authFallbackUrl || !shouldLockAuthBack()) {
            return;
        }

        window.history.replaceState({ authLockBase: true }, '', authFallbackUrl);
        window.history.pushState({ authLockTop: true }, '', authFallbackUrl);
    };

    window.addEventListener('popstate', (event) => {
        if (!isAuthDocument() || !shouldLockAuthBack()) {
            return;
        }

        event.stopImmediatePropagation();
        forceAuthFallback();

        if (toPath(window.location.href) === authFallbackUrl) {
            window.history.pushState({ authLockTop: true }, '', authFallbackUrl);
        }
    }, true);

    primeAuthBackLock();

    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) {
            return;
        }

        if (isAuthDocument()) {
            primeAuthBackLock();
            return;
        }

        window.location.reload();
    });

    const installSafeLoadContent = () => {
        if (typeof window.loadContent !== 'function') {
            return false;
        }

        if (window.loadContent.__historyGuardWrapped) {
            return true;
        }

        const safeLoadContent = async (rawUrl, options = {}) => {
            const targetUrl = toPath(rawUrl);
            const {
                containerSelector = '#main-content',
                updateHistory = true,
                onSuccess = null,
            } = options;

            if (isAuthDocument() && !isAuthPath(targetUrl)) {
                forceAuthFallback();
                return;
            }

            const container = document.querySelector(containerSelector);
            if (!container) {
                navigateHard(targetUrl);
                return;
            }

            container.classList.add('loading');
            const loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = 'block';
            }

            const stopLoadingState = () => {
                container.classList.remove('loading');
                if (loader) {
                    loader.style.display = 'none';
                }
            };

            try {
                const response = await fetch(targetUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const finalUrl = toPath(response.url || targetUrl);
                if (response.redirected && isAuthPath(finalUrl)) {
                    stopLoadingState();
                    navigateHard(finalUrl, true);
                    return;
                }

                const html = await response.text();
                if (isFullHtmlDocument(html)) {
                    stopLoadingState();
                    navigateHard(finalUrl, isAuthPath(finalUrl));
                    return;
                }

                if (!response.ok) {
                    throw new Error('Error al cargar la vista');
                }

                container.innerHTML = html;

                if (typeof onSuccess === 'function') {
                    onSuccess();
                }

                stopLoadingState();

                if (updateHistory) {
                    window.history.pushState({ url: targetUrl }, '', targetUrl);
                }
            } catch (error) {
                console.error(error);
                stopLoadingState();
                navigateHard(targetUrl);
            }
        };

        safeLoadContent.__historyGuardWrapped = true;
        safeLoadContent.__historyGuardOriginal = window.loadContent;
        window.loadContent = safeLoadContent;
        return true;
    };

    if (!installSafeLoadContent()) {
        let attempts = 0;
        const timer = window.setInterval(() => {
            attempts += 1;
            if (installSafeLoadContent() || attempts >= 40) {
                window.clearInterval(timer);
            }
        }, 100);
    }
})();
