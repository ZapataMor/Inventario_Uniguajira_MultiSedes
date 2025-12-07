function initGroupFunctions() {
    // Inicializar formulario para crear grupo
    // ruta del form: /api/groups/create
    inicializarFormularioAjax('#formCrearGrupo', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            refrescarVistaGrupos();
            showToast(response);
        }
    });

    // Inicializar formulario para renombrar grupo
    // ruta del form: /api/groups/rename
    inicializarFormularioAjax('#formRenombrarGrupo', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            refrescarVistaGrupos();
            showToast(response);
        }
    });

    // Inicializar búsqueda de grupos
    iniciarBusqueda('searchGroup');

    console.log("Funciones de grupos inicializadas");
}


function btnRenombrarGrupo() {
    const id = selectedItem.id;
    const nombreActual = selectedItem.name;
    document.getElementById("grupoRenombrarId").value = id;
    document.getElementById("grupoRenombrarNombre").value = nombreActual;
    mostrarModal('#modalRenombrarGrupo');
}

// eliminarGrupo()
function btnEliminarGrupo() {
    const idGrupo = selectedItem.id;

    eliminarRegistro({
        url: `/api/groups/delete/${idGrupo}`,
        onSuccess: (response) => {
            refrescarVistaGrupos();
            showToast(response);
        }
    });
}


// ---------------------------------------------------------------------
// REFRESCAR LA VISTA DE GRUPOS SIN RECARGAR TODA LA PÁGINA
// ---------------------------------------------------------------------
async function refrescarVistaGrupos() {
    loadContent('/groups', {
        containerSelector: '.content', // Especifica el contenedor donde se reemplazará el contenido
        updateHistory: false,         // No es necesario actualizar el historial aquí
        onSuccess: () => {
            // Reinicializar eventos y formularios después de cargar el contenido
            initGroupFunctions();
        }
    });
}
