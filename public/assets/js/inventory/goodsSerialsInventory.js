// Inicializa las funciones relacionadas con bienes seriales del inventario
function initGoodsSerialsInventoryFunctions() {

    // La vista se recarga por AJAX: cualquier selección previa apunta a
    // tarjetas que ya no están en el DOM, así que se descarta.
    if (typeof deselectItem === 'function') {
        deselectItem();
    }

    actualizarBotonSeleccionarTodos(0);

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

// ── Mantenimiento masivo de seriales ────────────────────────────────────────

/**
 * La barra de control cambia según cuántos bienes haya seleccionados:
 * con uno se ofrecen las acciones individuales; con dos o más solo tiene
 * sentido el mantenimiento masivo.
 *
 * Se escucha el evento en lugar de `window._onSelectionUpdate` porque ese
 * hook ya lo ocupa el módulo de bienes y se pisarían entre sí.
 */
document.addEventListener('selection:update', (event) => {
    const { type, items } = event.detail;

    if (type !== 'serial-good') return;

    const esMasivo = items.length >= 2;

    document.querySelectorAll('#control-bar-serial-good [data-serial-bulk]')
        .forEach(btn => { btn.hidden = !esMasivo; });

    document.querySelectorAll('#control-bar-serial-good [data-serial-single]')
        .forEach(btn => { btn.hidden = esMasivo; });

    actualizarBotonSeleccionarTodos(items.length);
});

/**
 * El botón alterna entre marcar todo y limpiar, así que su texto sigue
 * al estado real de la cuadrícula.
 */
function actualizarBotonSeleccionarTodos(seleccionados) {
    const label = document.querySelector('#selectAllSerials [data-select-all-label]');
    if (!label) return;

    const total = document.querySelectorAll('#serialsGrid .card-item').length;

    label.textContent = total > 0 && seleccionados === total
        ? 'Quitar selección'
        : 'Seleccionar todos';
}

function btnRegistrarMantenimientoMasivo() {
    const seleccionados = selectedItems.filter(i => i.type === 'serial-good');

    if (seleccionados.length < 2) {
        showToast({ success: false, message: 'Selecciona al menos dos bienes seriales.' });
        return;
    }

    abrirModalMantenimientoMasivo(seleccionados);
}

function abrirModalMantenimientoMasivo(seleccionados) {
    document.getElementById('mantMasivoBien').value = seleccionados[0].element.dataset.name || '';
    document.getElementById('mantMasivoResumen').textContent =
        `${seleccionados.length} bienes`;

    const lista = document.getElementById('mantMasivoLista');
    lista.innerHTML = '';

    seleccionados.forEach(item => {
        const li = document.createElement('li');
        li.className = 'mant-batch-item';

        const serial = document.createElement('span');
        serial.className = 'mant-batch-serial';
        serial.textContent = item.element.dataset.serial || 'Sin serial';

        const marca = document.createElement('span');
        marca.className = 'mant-batch-brand';
        marca.textContent = item.element.dataset.brand || 'Sin marca';

        li.append(serial, marca);
        lista.appendChild(li);
    });

    document.getElementById('mantMasivoTitulo').value = '';
    document.getElementById('mantMasivoDescripcion').value = '';
    document.getElementById('mantMasivoFecha').value = new Date().toISOString().slice(0, 10);

    mostrarModal('#modalMantenimientoMasivo');
}

function submitMantenimientoMasivo() {
    const seleccionados = selectedItems.filter(i => i.type === 'serial-good');
    const title = document.getElementById('mantMasivoTitulo').value.trim();
    const date = document.getElementById('mantMasivoFecha').value;
    const description = document.getElementById('mantMasivoDescripcion').value.trim();

    if (seleccionados.length < 2) {
        showToast({ success: false, message: 'Selecciona al menos dos bienes seriales.' });
        return;
    }
    if (!title) {
        showToast({ success: false, message: 'El título es obligatorio.' });
        return;
    }
    if (!date) {
        showToast({ success: false, message: 'La fecha es obligatoria.' });
        return;
    }

    const boton = document.getElementById('mantMasivoSubmit');
    const textoOriginal = boton.innerHTML;
    boton.disabled = true;
    boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';

    fetch('/api/maintenances/batch-create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            equipment_ids: seleccionados.map(item => Number(item.id)),
            title,
            date,
            description,
        }),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            showToast({ success: false, message: res.message || 'Error al registrar el mantenimiento.' });
            return;
        }

        ocultarModal('#modalMantenimientoMasivo');
        deselectItem();
        showToast({ success: true, message: res.message });
    })
    .catch(() => showToast({ success: false, message: 'Error al registrar el mantenimiento.' }))
    .finally(() => {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
    });
}

// Los onclick inline de la vista necesitan handlers disponibles en window.
Object.assign(window, {
    initGoodsSerialsInventoryFunctions,
    btnEditarBienSerial,
    btnEliminarBienSerial,
    btnCambiarInventarioSerial,
    btnDarDeBajaBienSerial,
    btnRegistrarMantenimientoMasivo,
    submitMantenimientoMasivo,
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
