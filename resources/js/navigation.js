window.loadContent = async (url, options = {}) => {
    url = url.replace(window.location.origin, '');
    console.log(`Cargando contenido desde: ${url}`);

    const {
        containerSelector = '#main-content',
        updateHistory = true,
        onSuccess = null
    } = options;

    try {
        const container = document.querySelector(containerSelector);
        if (!container) throw new Error(`No se encontro el contenedor: ${containerSelector}`);

        container.classList.add('loading');
        const loader = document.getElementById('loader');
        if (loader) loader.style.display = 'block';

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error('Error al cargar la vista');
        const html = await response.text();

        container.innerHTML = html;

        if (onSuccess) onSuccess();
        container.classList.remove('loading');
        if (loader) loader.style.display = 'none';

        if (updateHistory) {
            window.history.pushState({ url }, '', url);
        }
    } catch (error) {
        console.error(error);
        alert('No se pudo cargar la pagina');
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
            window.history.pushState({ url }, '', url);
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
