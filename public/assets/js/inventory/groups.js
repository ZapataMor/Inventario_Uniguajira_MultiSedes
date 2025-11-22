function initGroupFunctions() {

    // Inicializar formulario para crear grupo
    // ruta del form: /api/groups/create
    inicializarFormularioAjax('#formCrearGrupo', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/inventory', false);
        }
    });

    // Inicializar formulario para renombrar grupo
    // ruta del form: /api/groups/rename
    inicializarFormularioAjax('#formRenombrarGrupo', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/inventory', false);
        }
    });
}

function mostrarModalCrearInventario() {
    // Obtener el ID del grupo actual del localStorage
    const currentGroupId = localStorage.getItem('openGroup');

    // Establecer el valor en el campo oculto
    if (currentGroupId) {
        document.getElementById('grupo_id_crear_inventario').value = currentGroupId;
    }

    // Mostrar el modal
    mostrarModal('#modalCrearInventario');
}

function btnRenombrarGrupo() {
    console.log(selectedItem); // mensaje de depuración
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
            if (response.success) {
                loadContent('/inventory', false);
            }
            showToast(response);
        }
    });
}

/**
 * Cargar los inventarios de un grupo y actualizar la vista sin recargar la página
 */
async function abrirGrupo(groupId) {
    try {
        const url = `/inventory/${groupId}/inventories`;

        const response = await fetch(url, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!response.ok) throw new Error("Error al cargar inventarios");

        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, "text/html");

        const nuevoContenido = parsed.querySelector(".content");
        if (!nuevoContenido) throw new Error("No se encontró .content en la respuesta");

        document.querySelector(".content").replaceWith(nuevoContenido);

        // Cambiar la URL sin recargar
        history.pushState({}, "", url);

        console.log("Inventarios cargados correctamente");

    } catch (err) {
        console.error(err);
        showToast({message: "Error al abrir el grupo", success: false});
    }
}
