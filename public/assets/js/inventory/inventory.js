// Función principal para inicializar todas las funciones de inventario
function initInventoryFunctions() {
    // Inicializar formulario para crear inventario
    inicializarFormularioAjax('#formCrearInventario', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refrescarVistaInventario();
        }
    });

    // Inicializar formulario para renombrar inventario
    inicializarFormularioAjax('#formRenombrarInventario', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refrescarVistaInventario();
        }
    });

    // Inicializar formulario para editar responsable del inventario
    inicializarFormularioAjax('#formEditarResponsable', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refrescarVistaInventario();
        }
    });

    // Inicializar la búsqueda de inventarios
    iniciarBusqueda('searchInventory');

    console.log("Funciones de inventario inicializadas");
}


/**
 * Refresca la vista actual de bienes del inventario SIN usar loadContent()
 */
async function refrescarVistaInventario() {

    const groupId = document.getElementById('group-name').getAttribute('data-id');
    const url = `/group/${groupId}`;
    loadContent(url, {
        containerSelector: '.content', // Especifica el contenedor donde se reemplazará el contenido
        updateHistory: false,         // No es necesario actualizar el historial aquí
        onSuccess: () => {
            // Reinicializar eventos y formularios después de cargar el contenido
            initInventoryFunctions();
        }
    });

}


// Función para abrir el modal de renombrar inventario
function btnRenombrarInventario() {
    // console.log(selectedItem); // mensaje de depuración
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
            showToast(response);
            refrescarVistaInventario();
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
