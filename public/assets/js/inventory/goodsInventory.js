// Inicializa las funciones relacionadas con bienes del inventario
function initGoodsInventoryFunctions() {

    const groupId = document.getElementById('inventory-name').getAttribute('data-group-id');
    const inventoryId = document.getElementById('inventory-name').getAttribute('data-id');

    // Inicializa el formulario para crear un bien en el inventario
    // ruta: /api/goods-inventory/create
    inicializarFormularioAjax('#formCrearBienInventario', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            const url = `/group/${groupId}/inventory/${inventoryId}`;;
            loadContent(url,
                { onSuccess: () => initGoodsInventoryFunctions() }
            )
        }
    });

    // Inicializa el formulario para editar un bien cantidad
    // ruta: /api/goods-inventory/update-quantity
    inicializarFormularioAjax('#formEditarBienCantidad', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            const url = `/group/${groupId}/inventory/${inventoryId}`;;
            loadContent(url,
                { onSuccess: () => initGoodsInventoryFunctions() }
            )
        }
    });


    // ------------------------------------------------------------


    // Inicializa el formulario para dar de baja un bien cantidad
    // ruta: /api/goods-inventory/delete-quantity
    inicializarFormularioAjax('#formDarDeBajaBienCantidad', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            const url = `/group/${groupId}/inventory/${inventoryId}`;;
            loadContent(url,
                { onSuccess: () => initGoodsInventoryFunctions() }
            )
        }
    });


    // ------------------------------------------------------------


    // Inicializar formulario para cambiar estado del inventario
    // ruta: /api/inventories/updateEstado
    inicializarFormularioAjax('#estadoInventarioForm', {
        onSuccess: (response, form) => {
            const estados = ['bueno', 'regular', 'malo'];
            const idx = form.querySelector('[name="estado"]').value;

            cambiarEstadoInventario(estados[idx - 1]);
        }
    });

    initAutocompleteForBien();

    // Inicializa la búsqueda de bienes en el inventario
    iniciarBusqueda('searchGoodInventory');

    console.log("Funciones de bienes del inventario inicializadas");
}


function btnAbrirModalCrearBien() {
    const inventoryId = document.getElementById('inventory-name').getAttribute('data-id');

    document.getElementById('inventarioId').value = inventoryId;
    document.getElementById('nombreBienEnInventario').value = '';
    document.getElementById('bien_id').value = '';
    document.getElementById('dynamicFields').style.display = 'none';

    mostrarModal('#modalCrearBienInventario');
}


function btnEliminarBienCantidad() {
    const idInventario = document.getElementById('inventory-name').getAttribute('data-id');
    const idBien = selectedItem.id;

    eliminarRegistro({
        url: `/api/goods-inventory/delete-quantity/${idInventario}/${idBien}`,
        onSuccess: (response) => {
            showToast(response);
            const groupId = document.getElementById('inventory-name').getAttribute('data-group-id');
            const inventoryId = document.getElementById('inventory-name').getAttribute('data-id');

            const url = `/group/${groupId}/inventory/${inventoryId}`;;
            loadContent(url,
                { onSuccess: () => initGoodsInventoryFunctions() }
            )
        }
    });
}


function btnEditarBienCantidad() {
    const card = selectedItem.element;
    const idInventario = document.getElementById('inventory-name').getAttribute('data-id');

    // Establecer los valores en el formulario
    document.getElementById('editBienId').value = card.dataset.id;
    document.getElementById('editInventarioId').value = idInventario;
    document.getElementById('editNombreBienCantidad').value = card.dataset.name;
    document.getElementById('editCantidadBien').value = card.dataset.cantidad;

    // Mostrar el modal de edición
    mostrarModal('#modalEditarBienCantidad');
}

// ------------------------------------------------------------

function btnDarDeBajaBienCantidad() {
    const idBien = selectedItem.id;
    const idInventario = document.getElementById('inventory-name').getAttribute('data-id');

    // Establecer los valores en el formulario
    document.getElementById('darDeBajaBienId').value = idBien;
    document.getElementById('darDeBajaInventarioId').value = idInventario;
    document.getElementById('darDeBajaNombreBienCantidad').value = selectedItem.name;
    document.getElementById('darDeBajaCantidadDisponible').value = selectedItem.cantidad;
    
    // Establecer el máximo permitido
    document.getElementById('darDeBajaCantidadBien').setAttribute('max', selectedItem.cantidad);
    document.getElementById('darDeBajaCantidadBien').value = 1; // Valor por defecto

    mostrarModal('#modalDarDeBajaBienCantidad');
}

// ------------------------------------------------------------