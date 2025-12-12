// Inicializa las funciones relacionadas con bienes seriales del inventario
function initGoodsSerialsInventoryFunctions() {
    // Inicializa el formulario para editar un bien serial
    inicializarFormularioAjax('#formEditarBienSerial', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            const url = document.getElementById('good-serial-inventory-name').getAttribute('data-url');
            loadContent(url,
                { onSuccess: () => initGoodsSerialsInventoryFunctions() }
            );
        }
    });

    // Inicializar la búsqueda de inventarios
    iniciarBusqueda('searchGoodsSerialsInventory');

    console.log('Funciones de bienes seriales del inventario inicializadas');
}


function btnEditarBienSerial() {
    if (!selectedItem || selectedItem.type !== 'serial-good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien serial' });
        return;
    }

    // Obtener el elemento seleccionado
    const card = selectedItem.element;    // Establecer los valores en el formulario
    document.getElementById('editBienEquipoId').value = card.dataset.id;
    document.getElementById('editNombreBien').value = card.dataset.name;
    document.getElementById('editDescripcionBien').value = card.dataset.description || '';
    document.getElementById('editMarcaBien').value = card.dataset.brand || '';
    document.getElementById('editModeloBien').value = card.dataset.model || '';
    document.getElementById('editSerialBien').value = card.dataset.serial || '';
    document.getElementById('editEstadoBien').value = card.dataset.status || 'activo';
    document.getElementById('editColorBien').value = card.dataset.color || '';
    document.getElementById('editCondicionBien').value = card.dataset.condition || '';
    document.getElementById('editFechaIngresoBien').value = card.dataset.entryDate || '';

    // Mostrar el modal de edición
    mostrarModal('#modalEditarBienSerial');
}


function btnEliminarBienSerial() {
    const idBienSerial = selectedItem.id;

    eliminarRegistro({
        url: `/api/goods-inventory/delete-serial/${idBienSerial}`,
        onSuccess: (response) => {
            showToast(response);

            const url = document.getElementById('good-serial-inventory-name').getAttribute('data-url');
            loadContent(url,
                { onSuccess: () => initGoodsSerialsInventoryFunctions() }
            );
        }
    });
}

