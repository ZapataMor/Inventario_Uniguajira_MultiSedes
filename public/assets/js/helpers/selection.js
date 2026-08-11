/**
 * selection.js - Utilidades para manejo de selección de elementos
 *
 * Este archivo contiene funciones para gestionar la selección de elementos en la aplicación.
 * Proporciona funcionalidades para seleccionar y deseleccionar elementos, así como para
 * actualizar la barra de control correspondiente al tipo de elemento seleccionado.
 *
 * Reglas de comportamiento:
 * - Por defecto solo se puede tener un elemento seleccionado a la vez
 * - Un contenedor marcado con `data-multi-select` permite seleccionar varios
 * - Al seleccionar un elemento se muestra su barra de control específica
 * - Un elemento seleccionado se puede deseleccionar haciendo clic en él nuevamente
 * - La selección se limpia al hacer clic fuera de cualquier elemento seleccionable
 * - La barra de control muestra el nombre del elemento, o el total si hay varios
 *
 * La implementación incluye protección contra deselección accidental cuando hay modales activos,
 * permitiendo interacciones simultáneas entre el sistema de modales y el sistema de selección.
 *
 * @version 2.0
 * @date 2025-04-20
 */


// Variable para almacenar el elemento seleccionado.
// En contenedores multi-selección apunta al último marcado.
let selectedItem = null;

// Todos los elementos seleccionados. En modo simple tiene 0 o 1 elementos.
let selectedItems = [];

// Variable para controlar si se permite la deselección
let allowDeselection = true;

// ── Multi-selección ─────────────────────────────────────────────────────────
let multiSelectedItems = [];

function toggleMultiSelectItem(element, event) {
    event.stopPropagation();

    const id = element.dataset.id;
    const idx = multiSelectedItems.findIndex(i => i.id === id);

    if (idx >= 0) {
        multiSelectedItems.splice(idx, 1);
        element.classList.remove('multi-selected');
    } else {
        multiSelectedItems.push({
            id:        element.dataset.id,
            name:      element.dataset.name,
            assetType: element.dataset.assetType,
            cantidad:  element.dataset.cantidad,
            element,
        });
        element.classList.add('multi-selected');
    }

    const cb = element.querySelector('.multi-check-input');
    if (cb) cb.checked = idx < 0;

    updateBatchBar();
}

function updateBatchBar() {
    const bar = document.getElementById('batch-action-bar');
    if (!bar) return;

    const count = multiSelectedItems.length;
    if (count > 0) {
        bar.classList.add('visible');
        const label = bar.querySelector('.batch-count');
        if (label) {
            label.textContent = `${count} bien${count > 1 ? 'es' : ''} seleccionado${count > 1 ? 's' : ''}`;
        }
        const moveBtn = bar.querySelector('button[onclick="btnMoverSeleccionados()"]');
        if (moveBtn) {
            moveBtn.disabled = count < 2;
            moveBtn.title = count < 2 ? 'Selecciona al menos 2 bienes para mover en grupo' : 'Mover seleccionados';
        }
    } else {
        bar.classList.remove('visible');
    }
}

function clearMultiSelection() {
    multiSelectedItems.forEach(i => {
        i.element.classList.remove('multi-selected');
        const cb = i.element.querySelector('.multi-check-input');
        if (cb) cb.checked = false;
    });
    multiSelectedItems = [];
    updateBatchBar();
}

Object.assign(window, { toggleMultiSelectItem, updateBatchBar, clearMultiSelection });

/**
 * Un contenedor con `data-multi-select` acumula selección en lugar de
 * reemplazarla. Fuera de él el comportamiento sigue siendo de uno a la vez.
 */
function allowsMultiSelect(element) {
    return element.closest('[data-multi-select]') !== null;
}

// Deja `selectedItem` apuntando al último elemento marcado.
function syncSelectedItem() {
    selectedItem = selectedItems.length ? selectedItems[selectedItems.length - 1] : null;
}

// Función para seleccionar un elemento
function toggleSelectItem(element) {
    // Si se hace clic en un botón dentro del elemento, no hacer nada
    if (event.target.tagName === 'BUTTON') return;

    const type = element.dataset.type;

    // Si el elemento ya está seleccionado, lo deseleccionamos
    if (element.classList.contains('selected')) {
        element.classList.remove('selected');
        selectedItems = selectedItems.filter(i => i.element !== element);
    } else {
        // En modo simple, seleccionar uno descarta el anterior
        if (!allowsMultiSelect(element)) {
            selectedItems.forEach(i => i.element.classList.remove('selected'));
            selectedItems = [];
        }

        element.classList.add('selected');
        selectedItems.push({
            id: element.dataset.id,
            name: element.dataset.name,
            type: type,
            element: element,
        });
    }

    syncSelectedItem();
    updateControlBar(type);
}

/**
 * Marca todas las tarjetas visibles de un contenedor multi-selección.
 * Si ya estaban todas marcadas, limpia la selección.
 */
function toggleSelectAllItems(containerSelector) {
    const root = document.querySelector(containerSelector);
    if (!root) return;

    const cards = Array.from(root.querySelectorAll('.card-item'))
        .filter(card => card.style.display !== 'none');

    if (!cards.length) return;

    const type = cards[0].dataset.type;
    const todasMarcadas = cards.every(card => card.classList.contains('selected'));

    // Se reconstruye la selección de este contenedor desde cero para no
    // duplicar entradas si ya había tarjetas marcadas a mano.
    cards.forEach(card => {
        card.classList.remove('selected');
        selectedItems = selectedItems.filter(i => i.element !== card);
    });

    if (!todasMarcadas) {
        cards.forEach(card => {
            card.classList.add('selected');
            selectedItems.push({
                id: card.dataset.id,
                name: card.dataset.name,
                type: card.dataset.type,
                element: card,
            });
        });
    }

    syncSelectedItem();
    updateControlBar(type);
}

// Función para actualizar la barra de control
function updateControlBar(type) {
    const controlBar = document.getElementById(`control-bar-${type}`);
    if (!controlBar) {
        return;
    }

    const items = selectedItems.filter(i => i.type === type);

    if (items.length) {
        controlBar.classList.add('visible');
        const nameElement = controlBar.querySelector('.selected-name');
        if (nameElement) {
            nameElement.textContent = items.length === 1
                ? items[0].name
                : `${items.length} seleccionados`;
        }
    } else {
        controlBar.classList.remove('visible');
    }

    // Hook opcional: permite que módulos externos reaccionen al cambio de selección
    if (typeof window._onSelectionUpdate === 'function') {
        window._onSelectionUpdate(type, selectedItem, items);
    }

    // Evento equivalente al hook anterior. Existe para que varios módulos
    // puedan reaccionar a la vez sin pisarse `window._onSelectionUpdate`.
    document.dispatchEvent(new CustomEvent('selection:update', {
        detail: { type, item: selectedItem, items },
    }));
}

// Función para limpiar la selección
function deselectItem() {
    if (!selectedItems.length) return;

    const type = selectedItems[0].type;

    selectedItems.forEach(i => i.element.classList.remove('selected'));
    selectedItems = [];
    selectedItem = null;

    updateControlBar(type);
}

// Manejador de eventos para clicks fuera de los elementos
function handleOutsideClick(event) {
    // Si la deselección no está permitida, no hacer nada
    if (!allowDeselection) return;

    // Si se hace clic en la barra de control, no deseleccionar
    if (event.target.closest('.control-bar')) return;

    const cardItem = event.target.closest('.card-item');
    // Si se hizo clic fuera de un item, limpiar la selección
    if (!cardItem) deselectItem();
}

// Función para inicializar la selección
function initializeSelection() {
    document.addEventListener('click', handleOutsideClick);
    console.log('Selection functionality initialized');
}

// Función para desactivar la selección
function deactivateSelection() {
    deselectItem();
    document.removeEventListener('click', handleOutsideClick);
    console.log('Selection functionality deactivated');
}
