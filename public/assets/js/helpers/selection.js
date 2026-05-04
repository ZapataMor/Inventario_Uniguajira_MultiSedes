/**
 * selection.js - Utilidades para manejo de selección de elementos
 *
 * Este archivo contiene funciones para gestionar la selección de elementos en la aplicación.
 * Proporciona funcionalidades para seleccionar y deseleccionar elementos, así como para
 * actualizar la barra de control correspondiente al tipo de elemento seleccionado.
 *
 * Reglas de comportamiento:
 * - Solo se puede tener un elemento seleccionado a la vez
 * - Al seleccionar un elemento se muestra su barra de control específica
 * - Un elemento seleccionado se puede deseleccionar haciendo clic en él nuevamente
 * - La selección se limpia al hacer clic fuera de cualquier elemento seleccionable
 * - La barra de control muestra el nombre del elemento seleccionado
 *
 * La implementación incluye protección contra deselección accidental cuando hay modales activos,
 * permitiendo interacciones simultáneas entre el sistema de modales y el sistema de selección.
 *
 * @version 2.0
 * @date 2025-04-20
 */


// Variable para almacenar el elemento seleccionado
let selectedItem = null;

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

// Función para seleccionar un elemento
function toggleSelectItem(element) {
    // Si se hace clic en un botón dentro del elemento, no hacer nada
    if (event.target.tagName === 'BUTTON') return;

    const itemId = element.dataset.id;
    const itemName = element.dataset.name;
    const type = element.dataset.type;

    // Si el elemento ya está seleccionado, lo deseleccionamos
    if (element.classList.contains('selected')) {
        element.classList.remove('selected');
        selectedItem = null;
    } else {
        // Deseleccionar el elemento anteriormente seleccionado (si existe)
        if (selectedItem) {
            selectedItem.element.classList.remove('selected');
        }

        // Seleccionar este elemento
        element.classList.add('selected');
        selectedItem = { id: itemId, name: itemName, type: type, element: element };
    }

    updateControlBar(type);
    // console.log(selectedItem); // Depuración: mostrar el elemento seleccionado
}

// Función para actualizar la barra de control
function updateControlBar(type) {
    const controlBar = document.getElementById(`control-bar-${type}`);
    if (!controlBar) {
        return;
    }

    if (selectedItem && selectedItem.type === type) {
        controlBar.classList.add('visible');
        const nameElement = controlBar.querySelector('.selected-name');
        if (nameElement) {
            nameElement.textContent = selectedItem.name;
        }
    } else {
        controlBar.classList.remove('visible');
    }
}

// Función para limpiar la selección
function deselectItem() {
    if (selectedItem) {
        selectedItem.element.classList.remove('selected');
        const type = selectedItem.type;
        selectedItem = null;
        updateControlBar(type);
    }
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
