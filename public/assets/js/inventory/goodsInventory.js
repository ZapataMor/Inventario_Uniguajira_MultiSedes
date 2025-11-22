// Inicializa las funciones relacionadas con bienes del inventario
function initGoodsInventoryFunctions() {
    // Inicializa el formulario para crear un bien en el inventario
    inicializarFormularioAjax('#formCrearBienInventario', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            const inventarioId = localStorage.getItem('openInventory');
            // abrirInventario(inventarioId, false);
            loadContent('/inventory', false);
        }
    });

    initAutocompleteForBien();

    // Inicializa el formulario para editar un bien serial
    inicializarFormularioAjax('#formEditarBienSerial', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/inventory', false);
        }
    });

    // Inicializa el formulario para editar un bien cantidad
    inicializarFormularioAjax('#formEditarBienCantidad', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/inventory', false);
        }
    });
}

function abrirModalCrearBien() {
    const inventarioId = localStorage.getItem('openInventory');
    if (!inventarioId) {
        showToast({ success: false, message: 'No se ha seleccionado un inventario abierto' });
        return;
    }

    document.getElementById('inventarioId').value = inventarioId;
    document.getElementById('nombreBienEnInventario').value = '';
    document.getElementById('bien_id').value = '';
    document.getElementById('dynamicFields').style.display = 'none';

    mostrarModal('#modalCrearBienInventario');
}

function cerrarInventarioSerial() {
    const divGoodsInventory = document.getElementById('goods-inventory');
    const divSerialsGoodsInventory = document.getElementById('serials-goods-inventory');

    // Ocultar la vista de bienes seriales y mostrar la vista principal de bienes
    divSerialsGoodsInventory.classList.add('hidden');
    divGoodsInventory.classList.remove('hidden');

    toggleInventoryControls(true);
}

async function abrirSeriales(assetId, inventoryId) {
    try {
        const url = `/inventory/${inventoryId}/goods/${assetId}/serials`;

        const response = await fetch(url, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!response.ok) throw new Error("Error al cargar los bienes seriales");

        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, "text/html");

        const nuevoContenido = parsed.querySelector(".content");
        if (!nuevoContenido) throw new Error("No se encontró .content en la respuesta");

        document.querySelector(".content").replaceWith(nuevoContenido);

        history.pushState({}, "", url);

    } catch (err) {
        console.error(err);
        showToast({ message: "Error al abrir los bienes seriales", success: false });
    }
}


function btnEliminarBienCantidad() {
    const idInventario = localStorage.getItem('openInventory');
    const idBien = selectedItem.id;

    eliminarRegistro({
        url: `/api/goods-inventory/delete-quantity/${idInventario}/${idBien}`,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/inventory', false);
        }
    });
}

function btnEliminarBienSerial() {  //TODO: por serial
    const idBienSerial = selectedItem.id;

    eliminarRegistro({
        url: `/api/goods-inventory/delete-serial/${idBienSerial}`,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/inventory', false);
        }
    });
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

function btnEditarBienCantidad() {
    const card = selectedItem.element;

    // Establecer los valores en el formulario
    document.getElementById('editBienId').value = card.dataset.id;
    document.getElementById('editInventarioId').value = card.dataset.inventarioId;
    document.getElementById('editNombreBienCantidad').value = card.dataset.name;
    document.getElementById('editCantidadBien').value = card.dataset.cantidad;

    // Mostrar el modal de edición
    mostrarModal('#modalEditarBienCantidad');
}


