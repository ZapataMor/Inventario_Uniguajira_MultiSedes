const PROFILE_PREVIOUS_URL_KEY = 'profile_previous_url';

function isProfilePath(url) {
    try {
        const normalizedUrl = new URL(url, window.location.origin);
        return normalizedUrl.pathname === '/profile';
    } catch (error) {
        return String(url || '').startsWith('/profile');
    }
}

function initProfileFunctions() {
    inicializarFormularioAjax('#formEditarPerfil', {
        onSuccess: (response) => {
            showToast(response);
            loadContent('/profile', {
                updateHistory: false,
                onSuccess: () => initProfileFunctions()
            });
        }
    });

    inicializarFormularioAjax('#formCambiarPassword', {
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
        }
    });
}

function openProfile(event) {
    if (event) {
        event.preventDefault();
    }

    const currentUrl = `${window.location.pathname}${window.location.search}`;
    if (!isProfilePath(currentUrl)) {
        sessionStorage.setItem(PROFILE_PREVIOUS_URL_KEY, currentUrl);
    }

    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.classList.add('hidden');
    }

    loadContent('/profile', {
        onSuccess: () => initProfileFunctions()
    });
}

function goBackFromProfile(event) {
    if (event) {
        event.preventDefault();
    }

    const backButton = document.getElementById('profileBackButton');
    const fallbackUrl = backButton?.dataset?.fallbackUrl || '/home';

    let targetUrl = sessionStorage.getItem(PROFILE_PREVIOUS_URL_KEY);
    if (!targetUrl || isProfilePath(targetUrl)) {
        targetUrl = fallbackUrl;
    }

    sessionStorage.removeItem(PROFILE_PREVIOUS_URL_KEY);

    loadContent(targetUrl, {
        onSuccess: () => {
            if (typeof window.initializeScripts === 'function') {
                window.initializeScripts(targetUrl);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('formEditarPerfil')) {
        initProfileFunctions();
    }
});
