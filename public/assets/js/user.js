function initUserFunctions() {
    configurePortalCreateUserScope();

    if (document.querySelector('#formCrearUser')) {
        inicializarFormularioAjax('#formCrearUser', {
            resetOnSuccess: true,
            closeModalOnSuccess: true,
            onSuccess: (response) => {
                showToast(response);
                refreshUsers();
            }
        });
    }

    if (document.querySelector('#formEditarUser')) {
        inicializarFormularioAjax('#formEditarUser', {
            closeModalOnSuccess: true,
            onSuccess: (response) => {
                showToast(response);
                refreshUsers();
            }
        });
    }

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        const applySearch = () => {
            const filter = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('[data-user-row]');

            rows.forEach((row) => {
                const searchableText = (row.dataset.search || row.textContent || '').toLowerCase();
                row.style.display = searchableText.includes(filter) ? '' : 'none';
            });
        };

        ['input', 'keyup', 'search'].forEach((eventName) => {
            searchInput.addEventListener(eventName, applySearch);
        });

        applySearch();
    }

    initPortalUserDropdowns();

    const modalConfirm = document.getElementById('modalConfirmarEliminar');
    if (modalConfirm) {
        modalConfirm.addEventListener('hidden.bs.modal', function () {
            document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        });
    }
}

function configurePortalCreateUserScope() {
    const scopeSelect = document.getElementById('create-target-scope');
    const roleSelect = document.getElementById('create-role');
    const roleHelp = document.getElementById('create-role-help');

    if (!scopeSelect || !roleSelect) {
        return;
    }

    const buildOptions = (targetScope) => {
        const roleOptions = targetScope === 'portal'
            ? [{ value: 'super_administrador', label: 'Super Administrador' }]
            : [
                { value: 'administrador', label: 'Administrador' },
                { value: 'consultor', label: 'Consultor' },
            ];

        const currentValue = roleSelect.value;
        roleSelect.innerHTML = '';

        roleOptions.forEach((optionData) => {
            const option = document.createElement('option');
            option.value = optionData.value;
            option.textContent = optionData.label;
            roleSelect.appendChild(option);
        });

        const canKeepCurrent = roleOptions.some((optionData) => optionData.value === currentValue);
        roleSelect.value = canKeepCurrent ? currentValue : roleOptions[0].value;

        if (roleHelp) {
            roleHelp.textContent = targetScope === 'portal'
                ? 'En portal solo se permite Super Administrador.'
                : 'En sede solo se permite Administrador o Consultor.';
        }
    };

    scopeSelect.addEventListener('change', () => {
        buildOptions(scopeSelect.value);
    });

    buildOptions(scopeSelect.value);
}

function initPortalUserDropdowns() {
    const dropdowns = document.querySelectorAll('[data-sede-dropdown]');
    const searchInput = document.getElementById('searchInput');

    if (!dropdowns.length || !searchInput) {
        return;
    }

    const controllers = Array.from(dropdowns).map((dropdown) => ({
        dropdown,
        controller: getSedeDropdownController(dropdown, '.inventory-sede-body'),
    }));

    const updateDropdownState = () => {
        const hasFilter = searchInput.value.trim().length > 0;

        controllers.forEach(({ dropdown, controller }) => {
            const rows = dropdown.querySelectorAll('[data-user-row]');
            const visibleRows = Array.from(rows).filter((row) => row.style.display !== 'none');
            const visibleCountBadge = dropdown.querySelector('[data-visible-count]');
            const emptyByFilterMessage = dropdown.querySelector('[data-sede-empty]');

            if (visibleCountBadge) {
                visibleCountBadge.textContent = String(visibleRows.length);
            }

            if (emptyByFilterMessage) {
                emptyByFilterMessage.classList.toggle('hidden', visibleRows.length > 0);
            }

            if (hasFilter) {
                controller.setOpen(visibleRows.length > 0, true);
            } else {
                controller.setOpen(false, true);
            }
        });
    };

    ['input', 'keyup', 'search'].forEach((eventName) => {
        searchInput.addEventListener(eventName, updateDropdownState);
    });

    updateDropdownState();
}

function getSedeDropdownController(dropdown, bodySelector) {
    if (typeof createSedeDropdownController === 'function') {
        return createSedeDropdownController(dropdown, bodySelector);
    }

    return {
        setOpen: (shouldOpen) => {
            dropdown.open = shouldOpen;
        }
    };
}

async function refreshUsers() {
    try {
        const response = await fetch(window.location.pathname, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
            throw new Error('Error al refrescar usuarios');
        }

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nuevo = doc.querySelector('.content');
        const actual = document.querySelector('.content');

        if (nuevo && actual) {
            actual.replaceWith(nuevo);
            initUserFunctions();
        }
    } catch (error) {
        console.error(error);
        showToast({
            type: 'error',
            message: 'No se pudo actualizar la vista'
        });
    }
}

function btnEditarUser(element) {
    const id = element.getAttribute('data-id');
    const nombre = element.getAttribute('data-nombre');
    const nombreUsuario = element.getAttribute('data-nombre-usuario');
    const email = element.getAttribute('data-email');
    const rol = element.getAttribute('data-role');

    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nombre').value = nombre;
    document.getElementById('edit-nombre_usuario').value = nombreUsuario;
    document.getElementById('edit-email').value = email;

    const roleSelect = document.getElementById('edit-role');
    if (roleSelect && rol) {
        roleSelect.value = rol;
    }

    mostrarModal('#modalEditarUsuario');
}

function mostrarConfirmacion(userId) {
    const btnConfirmar = document.getElementById('btnConfirmarEliminar');

    btnConfirmar.setAttribute('data-id', userId);

    mostrarModal('#modalConfirmarEliminar');
}

function eliminarUsuario(id) {
    eliminarRegistro({
        url: `api/users/delete/${id}`,
        confirmTitle: 'Eliminar usuario',
        confirmText: '¿Deseas eliminar este usuario definitivamente?',
        onSuccess: (response) => {
            loadContent('/users', {
                onSuccess: () => initUserFunctions()
            });
            showToast(response);
        }
    });
}