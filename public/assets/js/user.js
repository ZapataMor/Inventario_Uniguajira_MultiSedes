function initUserFunctions() {

    // ==========================
    // FORM CREAR
    // ==========================
    inicializarFormularioAjax('#formCrearUser', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            
            // ✅ Actualizar vista sin recargar
            refreshUsers();
        }
    });

    // ==========================
    // FORM EDITAR
    // ==========================
    inicializarFormularioAjax('#formEditarUser', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            
            // ✅ Actualizar vista sin recargar
            refreshUsers();
        }
    });

    // ==========================
    // BUSCADOR (filtrado front)
    // ==========================
    const searchInput = document.getElementById('searchInput');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // ==========================
    // FIX: Bootstrap modal bug
    // (cuando se queda pegado)
    // ==========================
    const modalConfirm = document.getElementById('modalConfirmarEliminar');

    if (modalConfirm) {
        modalConfirm.addEventListener('hidden.bs.modal', function () {
            // 🔥 Elimina el backdrop si queda pegado
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

            // 🔥 Devuelve scroll normal
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        });
    }
}

// ==========================
// ✅ FUNCIÓN DE REFRESH
// ==========================
async function refreshUsers() {
    try {
        const response = await fetch(window.location.pathname, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!response.ok) throw new Error('Error al refrescar usuarios');

        const html = await response.text();

        // El controlador devuelve el @section('content') completo via AJAX.
        // Parseamos ese HTML parcial y extraemos el <div class="content">
        // para reemplazar el que está en el DOM sin recargar la página.
        const parser = new DOMParser();
        const doc    = parser.parseFromString(html, 'text/html');
        const nuevo  = doc.querySelector('.content');
        const actual = document.querySelector('.content');

        if (nuevo && actual) {
            actual.replaceWith(nuevo);
            // Reinicializar eventos sobre los nuevos elementos del DOM
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

// ==========================
// ABRIR MODAL EDITAR
// ==========================
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

    // Rol (select)
    const roleSelect = document.getElementById('edit-role');
    if (roleSelect && rol) {
        roleSelect.value = rol;
    }

    mostrarModal('#modalEditarUsuario');
}

// ==========================
// ABRIR MODAL CONFIRMAR
// ==========================
function mostrarConfirmacion(userId) {
    const btnConfirmar = document.getElementById('btnConfirmarEliminar');

    btnConfirmar.setAttribute('data-id', userId);

    mostrarModal('#modalConfirmarEliminar');
}

// ==========================
// ELIMINAR USUARIO
// ==========================
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