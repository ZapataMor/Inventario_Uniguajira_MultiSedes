// Función principal para inicializar todas las funciones de inventario
function initInventoryFunctions() {
    // Inicializar formulario para crear inventario
    inicializarFormularioAjax('#formCrearInventario', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            loadContent('/inventory', false);
        }
    });

    // Inicializar formulario para renombrar inventario
    inicializarFormularioAjax('#formRenombrarInventario', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            console.log("hola esto es un cambio")

            loadContent('/inventory', false);
        }
    });

    // Inicializar formulario para editar responsable del inventario
    inicializarFormularioAjax('#formEditarResponsable', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/inventory', false);
        }
    });

}

/**
 * Cargar los bienes de un inventario
 */
async function abrirInventario(inventoryId) {
    try {
        const url = `/inventory/${inventoryId}/goods`;

        const response = await fetch(url, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!response.ok) throw new Error("Error al cargar bienes");

        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, "text/html");

        const nuevoContenido = parsed.querySelector(".content");
        if (!nuevoContenido) throw new Error("No se encontró .content en la respuesta");

        document.querySelector(".content").replaceWith(nuevoContenido);

        // Actualizar URL sin recargar
        history.pushState({}, "", url);

        console.log("Bienes cargados correctamente");

    } catch (err) {
        console.error(err);
        showToast({ message: "Error al abrir el inventario", success: false });
    }
}


/**
 * Volver al listado de grupos
 */
async function volverAGroupIndex() {
    try {
        const url = `/inventory/groups`;

        const response = await fetch(url, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, "text/html");

        const nuevoContenido = parsed.querySelector(".content");
        document.querySelector(".content").replaceWith(nuevoContenido);

        history.pushState({}, "", url);

    } catch (error) {
        console.error(error);
        showToast("No se pudo volver a grupos", "error");
    }
}


// Función para abrir el modal de renombrar inventario
function btnRenombrarInventario() {
    console.log(selectedItem); // mensaje de depuración
    const id = selectedItem.id;
    const nombreActual = selectedItem.name;

    document.getElementById("renombrarInventarioId").value = id;
    document.getElementById("renombrarInventarioNombre").value = nombreActual;

    mostrarModal('#modalRenombrarInventario');
}

// Función para eliminar inventario
function btnEliminarInventario() {
    const idInventario = selectedItem.id;

    eliminarRegistro({
        url: `/api/inventories/delete/${idInventario}`,
        onSuccess: (response) => {
            if (response.success) {
                const grupoId = localStorage.getItem('openGroup');
                loadContent('/inventory', false);
            }
            showToast(response);
        }
    });
}

// Función para abrir el modal de editar responsable del inventario
function btnEditarResponsable() {
    // Agrega el id del inventario desde localStorage al input oculto antes de enviar
    const idInventario = localStorage.getItem('openInventory');
    if (idInventario) {
        document.getElementById('editarResponsableId').value = idInventario;
    }
    mostrarModal('#modalEditarResponsable');
}


// ==============================================================================
// ============ FUNCIONES PARA INFORMACION DEL INVENTARIO  ======================
// ============ ESTADO RESPONSABLE Y COMPONENTES ASOSIADOS ======================
// ==============================================================================


// Función para cambiar el estado del inventario (funcion compactada)
function cambiarEstadoInventario(estado) {
    document.querySelectorAll('.light').forEach(luz => luz.classList.remove('active', 'inactive'));
    const estados = { malo: '.light-red', regular: '.light-yellow', bueno: '.light-green' };
    Object.values(estados).forEach(sel => document.querySelector(sel)?.classList.add('inactive'));
    const luz = document.querySelector(estados[estado]);
    if (luz) luz.classList.remove('inactive'), luz.classList.add('active');
    // console.log(`Estado cambiado a: ${estado}`);
}

// Actualiza la información visual del inventario seleccionado
function actualizarInfoInventario(idInventory) {
    const inventoryCard = document.querySelector(`#inventories-content .card-item[data-id="${idInventory}"]`);

    // Actualizar el nombre del inventario
    const inventoryName = inventoryCard.getAttribute('data-name');
    const nameElem = document.getElementById('inventory-name');
    nameElem.innerText = inventoryName;

    // Actualizar el responsable del inventario
    const responsable = inventoryCard.getAttribute('data-responsable');
    const responsableElem = document.getElementById('responsable-inventario');
    responsableElem.innerText = responsable ? `- Responsable: ${responsable}` : '';

    // Actualizar el id del inventario en el input oculto
    const idInput = document.getElementById('estado_id_inventario');
    idInput.value = idInventory;

    // Cambiar el estado visual del inventario
    cambiarEstadoInventario(inventoryCard.dataset.estado);

}

// Función para inicializar el formulario de actualizar estado
// Esta funcion es llamada en sidebar.js
function initEstadoInventarioForm() {
    // Inicializar formulario para cambiar estado del inventario
    inicializarFormularioAjax('#estadoInventarioForm', {
        onSuccess: (response, form) => {
            const estados = ['bueno', 'regular', 'malo'];
            const idx = form.querySelector('[name="estado"]').value;

            cambiarEstadoInventario(estados[idx - 1]);
        }
    });
}

function toggleInventoryControls(show) {
    const controls = document.querySelector('.inventory-controls');
    if (show) {
        controls.classList.remove('hidden');
    } else {
        controls.classList.add('hidden');
    }
}
