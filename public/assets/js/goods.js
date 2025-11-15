let formsBienInicializado = false;

function initFormsBien() {
    if (formsBienInicializado) return;
    formsBienInicializado = true;

    inicializarFormularioAjax('#formCrearBien', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refrescarVistaBienes();
        }
    });

    inicializarFormularioAjax('#formActualizarBien', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refrescarVistaBienes();
        }
    });
}

function eliminarBien(id) {
    eliminarRegistro({
        url: `/api/goods/delete/${id}`,
        onSuccess: (response) => {
            // window.globalAutocomplete.recargarDatos(); // Usar window.globalAutocomplete
            refrescarVistaBienes();
            showToast(response);
        }
    });
}

function ActualizarBien(id, nombre) {
    // Configurar los valores iniciales del formulario
    document.getElementById("actualizarId").value = id;
    document.getElementById("actualizarNombreBien").value = nombre;
    document.getElementById("actualizarImagenBien").value = ""; // Limpiar imagen seleccionada

    // Mostrar el modal
    mostrarModal('#modalActualizarBien')
}

// ---------------------------------------------------------------------
// REFRESCAR LA VISTA DE BIENES SIN RECARGAR TODA LA PÁGINA
// ---------------------------------------------------------------------
async function refrescarVistaBienes() {
    const response = await fetch('/goods', {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });

    const html = await response.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");

    const nuevoContenido = doc.querySelector('.content');

    if (nuevoContenido) {
        document.querySelector('.content').replaceWith(nuevoContenido);

        // 🔥 REINICIALIZAR EVENTOS Y FORMULARIOS
        initFormsBien();

        // 🔥 Si tienes otros scripts, agrégalos:
        // inicializarModales();
        // window.globalAutocomplete?.recargarDatos();
    }
}
