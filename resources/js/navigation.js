const AUTH_PATH_PREFIXES = [
    '/login',
    '/register',
    '/password',
    '/forgot-password',
    '/reset-password',
];

function resolveUrlPath(url) {
    const resolved = new URL(url, window.location.origin);
    return `${resolved.pathname}${resolved.search}`;
}

function isAuthPath(url) {
    const path = new URL(url, window.location.origin).pathname;
    return AUTH_PATH_PREFIXES.some(prefix => path.startsWith(prefix));
}

function isFullHtmlDocument(html) {
    const normalized = String(html ?? '').trimStart().toLowerCase();
    return normalized.startsWith('<!doctype html') || normalized.startsWith('<html');
}

window.loadContent = async (url, options = {}) => {
    url = resolveUrlPath(url);
    console.log(`Cargando contenido desde: ${url}`);

    const {
        containerSelector = '#main-content',
        updateHistory = true,
        onSuccess = null
    } = options;

    try {
        const container = document.querySelector(containerSelector);
        if (!container) {
            window.location.assign(url);
            return;
        }

        container.classList.add('loading');
        const loader = document.getElementById('loader');
        if (loader) loader.style.display = 'block';
        const stopLoadingState = () => {
            container.classList.remove('loading');
            if (loader) loader.style.display = 'none';
        };

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const finalUrl = resolveUrlPath(response.url || url);
        if (response.redirected && isAuthPath(finalUrl)) {
            stopLoadingState();
            window.location.replace(finalUrl);
            return;
        }

        const html = await response.text();
        if (isFullHtmlDocument(html)) {
            stopLoadingState();
            window.location.assign(finalUrl);
            return;
        }

        if (!response.ok) throw new Error('Error al cargar la vista');

        container.innerHTML = html;

        if (onSuccess) onSuccess();
        stopLoadingState();

        if (updateHistory) {
            window.history.pushState({ url }, '', url);
        }
    } catch (error) {
        console.error(error);
        alert('No se pudo cargar la pagina');
        const container = document.querySelector(containerSelector);
        if (container) container.classList.remove('loading');
        const loader = document.getElementById('loader');
        if (loader) loader.style.display = 'none';
    }
};

window.initializeScripts = (url) => {
    const path = new URL(url, window.location.origin)
        .pathname
        .split('/')
        .filter(Boolean)[0];

    const scriptMap = {
        home: 'initFormsTask',
        goods: 'initFormsBien',
        groups: 'initGroupFunctions',
        profile: 'initProfileFunctions',
        users: 'initUserFunctions',
        records: 'initHistorialFunctions',
        reports: 'initReportsModule'
    };

    const scriptName = scriptMap[path];
    if (scriptName && typeof window[scriptName] === 'function') {
        window[scriptName]();
    }

    console.log('Scripts inicializados', scriptName);
};

document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('a[data-nav]');

    links.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const url = link.getAttribute('href');

            loadContent(url, { onSuccess: () => initializeScripts(url) });
        });
    });

    window.addEventListener('popstate', (e) => {
        if (e.state?.url) {
            loadContent(e.state.url, {
                updateHistory: false,
                onSuccess: () => initializeScripts(e.state.url)
            });

            const path = window.location.pathname.split('/')[1];
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('selected'));

            const current = document.getElementById(path === 'inventory' ? 'inventories' : path);
            if (current) current.classList.add('selected');
        }
    });
});
