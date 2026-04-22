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

    // Inicializa el formulario para dar de baja un serial
    inicializarFormularioAjax('#formDarDeBajaBienSerial', {
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

    // ------------------------------------------------------------

    // Inicializa el formulario para cambiar un serial de inventario
    // ruta: /api/goods-inventory/move-serial
    inicializarFormularioAjax('#formCambiarInventarioSerial', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);

            const url = document.getElementById('good-serial-inventory-name').getAttribute('data-url');
            loadContent(url, { onSuccess: () => initGoodsSerialsInventoryFunctions() });
        }
    });

    // Listener de cambio de grupo para el modal de seriales (una sola vez)
    const selectGrupoSerialEl = document.getElementById('moverSerialGrupoDestino');
    if (selectGrupoSerialEl && !selectGrupoSerialEl.dataset.listenerBound) {
        selectGrupoSerialEl.dataset.listenerBound = '1';
        selectGrupoSerialEl.addEventListener('change', function () {
            const grupoId = this.value;
            const selectInventario = document.getElementById('moverSerialInventarioDestino');
            const inventarioActualId = document.querySelector('[data-url]')?.getAttribute('data-url')?.match(/inventory\/(\d+)/)?.[1];

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
                    if (inventarioActualId && String(inv.id) === String(inventarioActualId)) return;
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

    // Inicializar la búsqueda de inventarios
    iniciarBusqueda('searchGoodsSerialsInventory');

    console.log('Funciones de bienes seriales del inventario inicializadas');
}

// Editar bien seleccionado
function btnEditarBienSerial() {
    if (!selectedItem || selectedItem.type !== 'serial-good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien serial' });
        return;
    }

    // Obtener el elemento seleccionado
    const card = selectedItem.element;

    // Establecer los valores en el formulario
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
    if (!selectedItem || selectedItem.type !== 'serial-good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien serial' });
        return;
    }

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


// Cambiar serial de inventario
function btnCambiarInventarioSerial() {
    if (!selectedItem || selectedItem.type !== 'serial-good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien serial.' });
        return;
    }

    const card = selectedItem.element;

    document.getElementById('moverSerialEquipoId').value = card.dataset.id;
    document.getElementById('moverSerialNombreBien').value = card.dataset.name;
    document.getElementById('moverSerialSerial').value = card.dataset.serial || '';
    document.getElementById('moverSerialMarca').value = card.dataset.brand || '';

    // Resetear selectores
    const selectGrupo = document.getElementById('moverSerialGrupoDestino');
    const selectInventario = document.getElementById('moverSerialInventarioDestino');
    selectGrupo.innerHTML = '<option value="">Seleccionar grupo...</option>';
    selectInventario.innerHTML = '<option value="">Seleccionar inventario...</option>';
    selectInventario.disabled = true;

    // Cargar grupos
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

    mostrarModal('#modalCambiarInventarioSerial');
}


// Dar de baja bien serial
function btnDarDeBajaBienSerial() {
    if (!selectedItem || selectedItem.type !== 'serial-good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien serial' });
        return;
    }

    const card = selectedItem.element;

    // Campos hidden
    document.getElementById('darDeBajaSerialEquipoId').value = card.dataset.id;
    document.getElementById('darDeBajaSerialInventarioId').value = card.dataset.inventoryId;

    // Nombre fila completa
    document.getElementById('darDeBajaSerialNombreBien').value = card.dataset.name;

    // 2 columnas
    document.getElementById('darDeBajaSerialSerial').value = card.dataset.serial || '';
    document.getElementById('darDeBajaSerialEstado').value = card.dataset.status || '';

    document.getElementById('darDeBajaSerialMarca').value = card.dataset.brand || '';
    document.getElementById('darDeBajaSerialModelo').value = card.dataset.model || '';

    document.getElementById('darDeBajaSerialColor').value = card.dataset.color || '';
    document.getElementById('darDeBajaSerialCondicion').value = card.dataset.condition || '';

    // Fechas
    document.getElementById('darDeBajaSerialFechaIngreso').value = card.dataset.entryDate || '';
    document.getElementById('darDeBajaSerialFechaSalida').value = new Date().toISOString().slice(0, 10);

    // Descripción (fila completa)
    document.getElementById('darDeBajaSerialDescripcion').value = card.dataset.description || '';

    // Motivo
    document.getElementById('darDeBajaSerialMotivo').value = '';

    mostrarModal('#modalDarDeBajaBienSerial');
}

// Los onclick inline de la vista necesitan handlers disponibles en window.
Object.assign(window, {
    initGoodsSerialsInventoryFunctions,
    btnEditarBienSerial,
    btnEliminarBienSerial,
    btnCambiarInventarioSerial,
    btnDarDeBajaBienSerial,
});

(function installGoodsSerialInventoryControlActions() {
    if (window.goodsSerialInventoryControlActionsBound) {
        return;
    }

    window.goodsSerialInventoryControlActionsBound = true;

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }

        const actions = {
            'cambiar-inventario-serial': btnCambiarInventarioSerial,
            'dar-baja-serial': btnDarDeBajaBienSerial,
            'editar-serial': btnEditarBienSerial,
            'eliminar-serial': btnEliminarBienSerial,
        };

        const handler = actions[button.dataset.action];
        if (!handler) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        handler();
    });
})();
