// removedGoods.js
// Funciones para la gestión de bienes dados de baja

function initRemovedGoodsFunctions() {
    // Inicializar búsqueda usando la función helper existente
    iniciarBusqueda('searchRemovedGoods');

    console.log("Funciones de bienes dados de baja inicializadas");
}

/**
 * Ver detalles completos de un bien dado de baja
 */
function btnViewRemovedDetails(id) {
    // Obtener los datos del bien desde el servidor
    fetch(`/removed/${id}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al cargar los detalles');
        }
        return response.text();
    })
    .then(html => {
        document.getElementById('removedDetailsContent').innerHTML = html;
        mostrarModal('#modalRemovedDetails');
    })
    .catch(error => {
        console.error('Error:', error);
        showToast({
            success: false,
            message: 'Error al cargar los detalles del bien'
        });
    });
}