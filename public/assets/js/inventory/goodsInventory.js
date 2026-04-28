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
    if (!selectedItem || selectedItem.type !== 'good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien.' });
        return;
    }

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
    if (!selectedItem || selectedItem.type !== 'good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien.' });
        return;
    }

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
    if (!selectedItem || selectedItem.type !== 'good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien.' });
        return;
    }

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
    if (!selectedItem || selectedItem.type !== 'good') {
        showToast({ success: false, message: 'No se ha seleccionado un bien.' });
        return;
    }

    const idBien = selectedItem.id;
    const idInventario = document.getElementById('inventory-name').getAttribute('data-id');
    const cantidadDisponible = selectedItem.element.dataset.cantidad;

    // Establecer los valores en el formulario
    document.getElementById('darDeBajaBienId').value = idBien;
    document.getElementById('darDeBajaInventarioId').value = idInventario;
    document.getElementById('darDeBajaNombreBienCantidad').value = selectedItem.name;
    document.getElementById('darDeBajaCantidadDisponible').value = cantidadDisponible;
    
    // Establecer el máximo permitido
    document.getElementById('darDeBajaCantidadBien').setAttribute('max', cantidadDisponible);
    document.getElementById('darDeBajaCantidadBien').value = 1; // Valor por defecto

    mostrarModal('#modalDarDeBajaBienCantidad');
}

// ------------------------------------------------------------
// ── Multi-selección: mover en lote ──────────────────────────────────────────

// Cache de grupos para no pedir N veces cuando hay muchos bienes
let _batchGruposCache = null;

function _fetchGrupos() {
    if (_batchGruposCache) return Promise.resolve(_batchGruposCache);
    return fetch('/api/groups/getAll', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            _batchGruposCache = Array.isArray(data) ? data : (data.groups ?? []);
            return _batchGruposCache;
        });
}

function _fetchInventariosPorGrupo(grupoId, excludeId) {
    return fetch(`/api/inventories/getByGroupId/${grupoId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(r => r.json())
    .then(data => {
        const invs = Array.isArray(data) ? data : (data.inventories ?? []);
        return invs.filter(inv => String(inv.id) !== String(excludeId));
    });
}

function _crearSelectGrupo(placeholder) {
    const sel = document.createElement('select');
    sel.className = 'form-input';
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    return sel;
}

function _crearSelectInventario(placeholder) {
    const sel = document.createElement('select');
    sel.className = 'form-input';
    sel.disabled = true;
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    return sel;
}

function _bindGrupoInvChange(selGrupo, selInv, inventarioActualId) {
    selGrupo.addEventListener('change', function () {
        const grupoId = this.value;
        selInv.innerHTML = '<option value="">Inventario...</option>';
        selInv.disabled = true;
        if (!grupoId) return;

        _fetchInventariosPorGrupo(grupoId, inventarioActualId)
            .then(invs => {
                invs.forEach(inv => {
                    const opt = document.createElement('option');
                    opt.value = inv.id;
                    opt.textContent = inv.nombre;
                    selInv.appendChild(opt);
                });
                selInv.disabled = false;
            })
            .catch(() => showToast({ success: false, message: 'Error al cargar inventarios.' }));
    });
}

function _buildBienRow(item, mode) {
    const inventoryId = document.getElementById('inventory-name').getAttribute('data-id');
    const row = document.createElement('div');
    row.className = 'batch-bien-row';
    row.dataset.id   = item.id;
    row.dataset.type = item.assetType;

    // Parte superior: nombre + cantidad (si aplica)
    const main = document.createElement('div');
    main.className = 'batch-bien-main';
    main.innerHTML = `
        <div class="batch-bien-info">
            <span class="batch-bien-name">${item.name}</span>
            <span class="batch-bien-type">${
                item.assetType === 'Cantidad'
                    ? 'Cantidad disponible: ' + item.cantidad
                    : 'Serial — seleccione los equipos a mover'
            }</span>
        </div>
    `;

    if (item.assetType === 'Cantidad') {
        const cantInput = document.createElement('input');
        cantInput.type = 'number';
        cantInput.className = 'form-input batch-cantidad-input';
        cantInput.dataset.id = item.id;
        cantInput.min = 1;
        cantInput.max = item.cantidad;
        cantInput.value = 1;
        cantInput.required = true;
        main.appendChild(cantInput);
    }

    row.appendChild(main);

    // Lista de equipos seriales con checkboxes
    if (item.assetType === 'Serial') {
        const serialsWrapper = document.createElement('div');
        serialsWrapper.className = 'batch-serials-wrapper';
        serialsWrapper.innerHTML = '<span class="batch-serials-loading"><i class="fas fa-spinner fa-spin"></i> Cargando equipos...</span>';
        row.appendChild(serialsWrapper);

        fetch(`/api/goods-inventory/equipments/${inventoryId}/${item.id}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(equipments => {
            if (!equipments.length) {
                serialsWrapper.innerHTML = '<span class="batch-serials-empty">Sin equipos disponibles.</span>';
                return;
            }

            const toggleLink = document.createElement('div');
            toggleLink.className = 'batch-serials-toggle';
            toggleLink.innerHTML = '<a href="#">Seleccionar todos</a> / <a href="#">Ninguno</a>';
            toggleLink.querySelectorAll('a').forEach((a, i) => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    serialsWrapper.querySelectorAll('.serial-check-input').forEach(cb => {
                        cb.checked = i === 0;
                    });
                });
            });

            const list = document.createElement('div');
            list.className = 'batch-serials-list';

            equipments.forEach(eq => {
                const label = document.createElement('label');
                label.className = 'batch-serial-item';
                label.innerHTML = `
                    <input type="checkbox" class="serial-check-input" value="${eq.id}" checked>
                    <span class="serial-check-label">
                        <b>${eq.serial}</b>
                        ${eq.brand  ? ' · ' + eq.brand  : ''}
                        ${eq.model  ? ' · ' + eq.model  : ''}
                        ${eq.status ? ' · ' + eq.status : ''}
                    </span>
                `;
                list.appendChild(label);
            });

            serialsWrapper.innerHTML = '';
            serialsWrapper.appendChild(toggleLink);
            serialsWrapper.appendChild(list);
        })
        .catch(() => {
            serialsWrapper.innerHTML = '<span class="batch-serials-empty">Error al cargar equipos.</span>';
        });
    }

    // Destino por fila (solo en modo "diferentes")
    if (mode === 'diferentes') {
        const destinoDiv = document.createElement('div');
        destinoDiv.className = 'batch-row-destino';

        const selGrupo = _crearSelectGrupo('Grupo...');
        const selInv   = _crearSelectInventario('Inventario...');

        selGrupo.dataset.rowId = item.id;
        selInv.dataset.rowId   = item.id;

        destinoDiv.appendChild(selGrupo);
        destinoDiv.appendChild(selInv);
        row.appendChild(destinoDiv);

        _bindGrupoInvChange(selGrupo, selInv, inventoryId);

        _fetchGrupos().then(grupos => {
            grupos.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.nombre;
                selGrupo.appendChild(opt);
            });
        }).catch(() => {});
    }

    return row;
}

function buildBatchBienesList(mode) {
    const lista = document.getElementById('batchBienesList');
    lista.innerHTML = '';
    multiSelectedItems.forEach(item => {
        lista.appendChild(_buildBienRow(item, mode));
    });
}

function onBatchModeChange(mode) {
    // Actualizar estilo del toggle
    document.getElementById('batchLabelMismo').classList.toggle('active', mode === 'mismo');
    document.getElementById('batchLabelDiferentes').classList.toggle('active', mode === 'diferentes');

    // Mostrar/ocultar sección de destino global
    const globalSection = document.getElementById('batchDestinoGlobal');
    if (globalSection) globalSection.style.display = mode === 'mismo' ? '' : 'none';

    buildBatchBienesList(mode);
}

function btnMoverSeleccionados() {
    if (!multiSelectedItems || multiSelectedItems.length === 0) {
        showToast({ success: false, message: 'No hay bienes seleccionados.' });
        return;
    }

    const inventoryId = document.getElementById('inventory-name').getAttribute('data-id');
    document.getElementById('batchMoverInventarioOrigenId').value = inventoryId;

    // Invalidar cache de grupos para obtener datos frescos
    _batchGruposCache = null;

    // Forzar modo "mismo" al abrir
    document.querySelectorAll('input[name="batchMode"]').forEach(r => {
        r.checked = r.value === 'mismo';
    });
    document.getElementById('batchLabelMismo').classList.add('active');
    document.getElementById('batchLabelDiferentes').classList.remove('active');

    const globalSection = document.getElementById('batchDestinoGlobal');
    if (globalSection) globalSection.style.display = '';

    buildBatchBienesList('mismo');

    // Inicializar selector global de grupo una sola vez por apertura
    const selGrupo = document.getElementById('batchMoverGrupoDestino');
    const selInv   = document.getElementById('batchMoverInventarioDestino');
    selGrupo.innerHTML = '<option value="">Seleccionar grupo...</option>';
    selInv.innerHTML   = '<option value="">Seleccionar inventario...</option>';
    selInv.disabled    = true;

    // Listener de grupo global (re-bind sin duplicar)
    const nuevoSelGrupo = selGrupo.cloneNode(true);
    selGrupo.replaceWith(nuevoSelGrupo);
    _bindGrupoInvChange(nuevoSelGrupo, selInv, inventoryId);

    _fetchGrupos().then(grupos => {
        grupos.forEach(g => {
            const opt = document.createElement('option');
            opt.value = g.id;
            opt.textContent = g.nombre;
            nuevoSelGrupo.appendChild(opt);
        });
    }).catch(() => showToast({ success: false, message: 'Error al cargar grupos.' }));

    mostrarModal('#modalMoverMultiplesBienes');
}

function submitBatchMove() {
    const sourceId = document.getElementById('batchMoverInventarioOrigenId').value;
    const mode     = document.querySelector('input[name="batchMode"]:checked')?.value ?? 'mismo';
    const lista    = document.getElementById('batchBienesList');

    // Destino global (modo "mismo")
    let globalDestinoId = null;
    if (mode === 'mismo') {
        globalDestinoId = document.getElementById('batchMoverInventarioDestino').value;
        if (!globalDestinoId) {
            showToast({ success: false, message: 'Seleccione el inventario destino.' });
            return;
        }
    }

    const bienes = [];
    let hayError = false;

    lista.querySelectorAll('.batch-bien-row').forEach(row => {
        if (hayError) return;

        const id        = row.dataset.id;
        const assetType = row.dataset.type;
        const nombre    = row.querySelector('.batch-bien-name')?.textContent ?? `ID ${id}`;

        // Determinar destino
        let destinoId = globalDestinoId;
        if (mode === 'diferentes') {
            const selInv = row.querySelector('.batch-row-destino select:last-child');
            destinoId = selInv?.value;
            if (!destinoId) {
                showToast({ success: false, message: `Seleccione inventario destino para "${nombre}".` });
                hayError = true;
                return;
            }
        }

        if (String(destinoId) === String(sourceId)) {
            showToast({ success: false, message: `El destino de "${nombre}" no puede ser el mismo inventario de origen.` });
            hayError = true;
            return;
        }

        const bienData = { id, type: assetType, destinoInventarioId: destinoId };

        if (assetType === 'Cantidad') {
            const cantInput = row.querySelector('.batch-cantidad-input');
            const cantidad  = parseInt(cantInput?.value ?? '0', 10);
            if (!cantidad || cantidad < 1) {
                showToast({ success: false, message: `Ingrese una cantidad válida para "${nombre}".` });
                hayError = true;
                return;
            }
            bienData.cantidad = cantidad;
        }

        if (assetType === 'Serial') {
            const checked = Array.from(row.querySelectorAll('.serial-check-input:checked'))
                                 .map(cb => parseInt(cb.value, 10));
            if (checked.length === 0) {
                showToast({ success: false, message: `Seleccione al menos un equipo serial de "${nombre}".` });
                hayError = true;
                return;
            }
            bienData.equipmentIds = checked;
        }

        bienes.push(bienData);
    });

    if (hayError || bienes.length === 0) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                   || document.querySelector('input[name="_token"]')?.value;

    const groupId     = document.getElementById('inventory-name').getAttribute('data-group-id');
    const inventoryId = document.getElementById('batchMoverInventarioOrigenId').value;

    fetch('/api/goods-inventory/batch-move-goods', {
        method: 'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ sourceInventarioId: sourceId, bienes }),
    })
    .then(r => r.json())
    .then(data => {
        showToast(data);
        if (data.success) {
            ocultarModal('#modalMoverMultiplesBienes');
            clearMultiSelection();
            loadContent(`/group/${groupId}/inventory/${inventoryId}`, { onSuccess: () => initGoodsInventoryFunctions() });
        }
    })
    .catch(() => showToast({ success: false, message: 'Error al procesar la solicitud.' }));
}

// Los onclick inline de la vista necesitan handlers disponibles en window.
Object.assign(window, {
    initGoodsInventoryFunctions,
    btnAbrirModalCrearBien,
    btnEliminarBienCantidad,
    btnEditarBienCantidad,
    btnCambiarInventario,
    btnDarDeBajaBienCantidad,
    btnMoverSeleccionados,
    onBatchModeChange,
    submitBatchMove,
});

(function installGoodsInventoryControlActions() {
    if (window.goodsInventoryControlActionsBound) {
        return;
    }

    window.goodsInventoryControlActionsBound = true;

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }

        const actions = {
            'cambiar-inventario': btnCambiarInventario,
            'dar-baja-cantidad': btnDarDeBajaBienCantidad,
            'editar-cantidad': btnEditarBienCantidad,
            'eliminar-cantidad': btnEliminarBienCantidad,
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
