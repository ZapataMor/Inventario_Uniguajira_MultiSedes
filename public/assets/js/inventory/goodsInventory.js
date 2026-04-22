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


    // Inicializa el formulario para cambiar un bien de inventario
    // ruta: /api/goods-inventory/move-good
    inicializarFormularioAjax('#formCambiarInventarioBien', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            const url = `/group/${groupId}/inventory/${inventoryId}`;
            loadContent(url, { onSuccess: () => initGoodsInventoryFunctions() });
        }
    });

    // Registrar listener de cambio de grupo (una sola vez por inicialización)
    const selectGrupoEl = document.getElementById('moverGrupoDestino');
    if (selectGrupoEl && !selectGrupoEl.dataset.listenerBound) {
        selectGrupoEl.dataset.listenerBound = '1';
        selectGrupoEl.addEventListener('change', function () {
            const grupoId = this.value;
            const selectInventario = document.getElementById('moverInventarioDestino');
            const inventarioActualId = document.getElementById('inventory-name').getAttribute('data-id');

            selectInventario.innerHTML = '<option value="">Seleccionar inventario...</option>';
            selectInventario.disabled = true;

            if (!grupoId) return;

            fetch(`/api/inventories/getByGroupId/${grupoId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                const inventarios = Array.isArray(data) ? data : (data.inventories ?? []);

                inventarios.forEach(inv => {
                    if (String(inv.id) === String(inventarioActualId)) return;
                    const opt = document.createElement('option');
                    opt.value = inv.id;
                    opt.textContent = inv.nombre;
                    selectInventario.appendChild(opt);
                });

                selectInventario.disabled = false;
            })
            .catch(() => showToast({ success: false, message: 'Error al cargar inventarios.' }));
        });
    }

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

    if (typeof initAutocompleteForBien === 'function') {
        initAutocompleteForBien();
    }

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

function btnCambiarInventario() {
    const card = selectedItem.element;
    const idBien = selectedItem.id;
    const idInventario = document.getElementById('inventory-name').getAttribute('data-id');
    const assetType = card.dataset.assetType;

    document.getElementById('moverBienId').value = idBien;
    document.getElementById('moverInventarioOrigenId').value = idInventario;
    document.getElementById('moverNombreBien').value = selectedItem.name;

    // Mostrar u ocultar sección de cantidad según tipo
    const cantidadSection = document.getElementById('moverCantidadSection');
    const cantidadInput = document.getElementById('moverCantidad');

    if (assetType === 'Cantidad') {
        cantidadSection.style.display = '';
        document.getElementById('moverCantidadDisponible').value = card.dataset.cantidad;
        cantidadInput.setAttribute('max', card.dataset.cantidad);
        cantidadInput.value = 1;
        cantidadInput.required = true;
    } else {
        cantidadSection.style.display = 'none';
        cantidadInput.required = false;
        cantidadInput.value = '';
    }

    // Resetear selectores de destino
    const selectGrupo = document.getElementById('moverGrupoDestino');
    const selectInventario = document.getElementById('moverInventarioDestino');
    selectGrupo.value = '';
    selectInventario.innerHTML = '<option value="">Seleccionar inventario...</option>';
    selectInventario.disabled = true;

    // Cargar grupos (siempre, para que el select esté actualizado)
    selectGrupo.innerHTML = '<option value="">Seleccionar grupo...</option>';
    fetch('/api/groups/getAll', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const grupos = Array.isArray(data) ? data : (data.groups ?? []);
            grupos.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.nombre;
                selectGrupo.appendChild(opt);
            });
        })
        .catch(() => showToast({ success: false, message: 'Error al cargar grupos.' }));

    mostrarModal('#modalCambiarInventarioBien');
}

// ------------------------------------------------------------

function btnDarDeBajaBienCantidad() {
    const idBien = selectedItem.id;
    const idInventario = document.getElementById('inventory-name').getAttribute('data-id');

    // Establecer los valores en el formulario
    document.getElementById('darDeBajaBienId').value = idBien;
    document.getElementById('darDeBajaInventarioId').value = idInventario;
    document.getElementById('darDeBajaNombreBienCantidad').value = selectedItem.name;
    document.getElementById('darDeBajaCantidadDisponible').value = selectedItem.element.dataset.cantidad;
    
    // Establecer el máximo permitido
    document.getElementById('darDeBajaCantidadBien').setAttribute('max', selectedItem.cantidad);
    document.getElementById('darDeBajaCantidadBien').value = 1; // Valor por defecto

    mostrarModal('#modalDarDeBajaBienCantidad');
}

// ------------------------------------------------------------
