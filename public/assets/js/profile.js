function initProfileFunctions() {
    inicializarFormularioAjax('#formEditarPerfil', {
        onSuccess: (response) => {
            showToast(response);
            loadContent('/profile', {
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

    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.classList.add('hidden');
    }

    loadContent('/profile', {
        onSuccess: () => initProfileFunctions()
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('formEditarPerfil')) {
        initProfileFunctions();
    }
});
