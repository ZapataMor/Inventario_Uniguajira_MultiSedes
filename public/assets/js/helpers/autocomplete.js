/**
 * autocompleteSearch.js - Funcionalidad configurable de autocompletado para búsquedas
 *
 * Este script proporciona un sistema de autocompletado configurable y reutilizable que
 * puede implementarse en cualquier campo de búsqueda. Obtiene datos de una API configurable
 * y ofrece sugerencias mientras el usuario escribe, con navegación mediante teclado y ratón.
 *
 *
 * Reglas de comportamiento:
 * - Muestra sugerencias filtradas mientras el usuario escribe en el campo
 * - Indica cuando no hay coincidencias o cuando no hay datos disponibles
 * - Permite navegar por las sugerencias usando las teclas de dirección (arriba/abajo)
 * - Selecciona automáticamente la única opción disponible al presionar Enter
 * - No realiza acción al presionar Enter si hay múltiples opciones sin selección
 * - Asigna el ID del elemento seleccionado al campo oculto correspondiente (si se especifica)
 * - Ejecuta una función callback personalizada al seleccionar un elemento (si se proporciona)
 * - Cierra las sugerencias al hacer clic fuera del contenedor o al presionar Escape
 * - Resalta visualmente la parte coincidente del texto en las sugerencias
 * - Previene el envío de formularios al presionar Enter cuando hay sugerencias activas
 * - Mantiene el elemento activo visible en la lista scrollable
 * - Restaura la posición del scroll al inicio cuando se realiza una nueva búsqueda
 *
 * @version 2.4
 * @date 2025-04-27
 */

/**
 * initAutocompleteSearch - Inicializa el autocompletado para campos de búsqueda
 *
 * @param {string} containerSelector - Selector CSS del contenedor que incluirá input y lista ul
 * @param {Object} options - Opciones de configuración
 * @param {string} options.dataUrl - URL para obtener los datos JSON
 * @param {string} options.inputSelector - Selector CSS del input dentro del contenedor (default: 'input')
 * @param {string} options.listSelector - Selector CSS de la lista de sugerencias (default: '.suggestions')
 * @param {string} options.dataKey - Propiedad del objeto JSON a usar como texto visible (default: segundo key)
 * @param {string} options.idKey - Propiedad del objeto JSON a usar como ID (default: primer key)
 * @param {string} options.hiddenInputSelector - Selector CSS del input oculto para almacenar el ID seleccionado (opcional)
 * @param {Function} options.onSelect - Función a ejecutar cuando se selecciona un elemento
 * @param {string} options.noMatchText - Texto a mostrar cuando no hay coincidencias (default: 'No hay coincidencias')
 * @param {string} options.noDataText - Texto a mostrar cuando no hay datos disponibles (default: 'No hay datos disponibles')
 * @returns {Object} - Objeto con métodos públicos (recargarDatos)
 */
function initAutocompleteSearch(containerSelector, options) {
    const container = document.querySelector(containerSelector);
    const input = container.querySelector(options.inputSelector);
    const list = container.querySelector(options.listSelector);
    const hiddenInput = container.querySelector(options.hiddenInputSelector);

    let activeIndex = -1;
    let items = [];
    let filteredItems = [];

    // Función para ocultar sugerencias
    function ocultarSugerencias() {
        list.innerHTML = '';
        list.style.display = 'none';
        activeIndex = -1;
    }

    // Función para hacer scroll al elemento activo
    function scrollToActiveItem() {
        const activeItem = list.querySelector('li.active');
        if (!activeItem) return;

        // Obtener posiciones y dimensiones
        const listRect = list.getBoundingClientRect();
        const itemRect = activeItem.getBoundingClientRect();

        // Verificar si el elemento está fuera de la vista
        if (itemRect.top < listRect.top) {
            // Si está por encima del área visible
            list.scrollTop -= (listRect.top - itemRect.top);
        } else if (itemRect.bottom > listRect.bottom) {
            // Si está por debajo del área visible
            list.scrollTop += (itemRect.bottom - listRect.bottom);
        }
    }

    // Función para mostrar sugerencias
    function mostrarSugerencias(sugerencias) {
        list.innerHTML = '';
        list.style.display = 'block';
        // Resetear el scroll de la lista al inicio
        list.scrollTop = 0;

        if (sugerencias.length === 0) {
            const li = document.createElement('li');
            li.classList.add('text-danger');
            li.textContent = options.noMatchText || 'Sin coincidencias';
            list.appendChild(li);
            return;
        }

        filteredItems = sugerencias;

        sugerencias.forEach((item, index) => {
            const li = document.createElement('li');
            const valor = input.value.trim().toLowerCase();
            const texto = item.bien;

            // Resaltar la parte coincidente del texto
            const textoLower = texto.toLowerCase();
            const inicio = textoLower.indexOf(valor);

            if (inicio >= 0) {
                const fin = inicio + valor.length;
                const parteAntes = texto.substring(0, inicio);
                const parteCoincidente = texto.substring(inicio, fin);
                const parteDespues = texto.substring(fin);

                // Usar innerHTML para permitir la etiqueta <strong>
                li.innerHTML = parteAntes + '<strong>' + parteCoincidente + '</strong>' + parteDespues;
            } else {
                li.textContent = texto;
            }

            li.dataset.index = index;

            li.addEventListener('click', () => {
                seleccionarItem(item);
                ocultarSugerencias();
            });

            li.addEventListener('mouseover', () => {
                activeIndex = index;
                marcarItemActivo();
            });

            list.appendChild(li);
        });

        // Si solo hay una sugerencia, marcarla como activa
        if (sugerencias.length === 1) {
            activeIndex = 0;
            marcarItemActivo();
        }
    }

    // Función para marcar item activo
    function marcarItemActivo() {
        const items = list.querySelectorAll('li');
        items.forEach(item => item.classList.remove('active'));

        if (activeIndex >= 0 && activeIndex < items.length) {
            items[activeIndex].classList.add('active');
            // Asegurar que el elemento activo esté visible en la lista
            scrollToActiveItem();
        }
    }

    // Función para seleccionar un item
    function seleccionarItem(item) {
        input.value = item.bien;
        hiddenInput.value = item.id;

        // Ocultar las sugerencias inmediatamente al seleccionar
        ocultarSugerencias();

        // Ejecutar la función de callback si existe
        if (typeof options.onSelect === 'function') {
            options.onSelect(item);
        }
    }

    // Manejar eventos de teclado
    input.addEventListener('keydown', (e) => {
        // Si las sugerencias están ocultas y no es la tecla Enter, no hacer nada
        if (list.style.display === 'none' && e.key !== 'Enter') {
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, filteredItems.length - 1);
                marcarItemActivo();
                break;

            case 'ArrowUp':
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                marcarItemActivo();
                break;

            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0 && filteredItems[activeIndex]) {
                    seleccionarItem(filteredItems[activeIndex]);
                } else if (filteredItems.length === 1) {
                    // Si solo hay una sugerencia, seleccionarla automáticamente
                    seleccionarItem(filteredItems[0]);
                }
                break;

            case 'Escape':
                e.preventDefault();
                ocultarSugerencias();
                break;
        }
    });

    // Buscar al escribir (sin debounce, respuesta inmediata)
    input.addEventListener('input', () => {
        const valor = input.value.trim().toLowerCase();

        // Resetear el índice activo al cambiar la búsqueda
        activeIndex = -1;

        if (valor === '') {
            ocultarSugerencias();
            hiddenInput.value = '';
            return;
        }

        if (items.length > 0) {
            // Filtrar los items ya cargados
            const sugerencias = items.filter(item =>
                item.bien.toLowerCase().includes(valor)
            );
            mostrarSugerencias(sugerencias);
        } else {
            // Cargar datos desde la API
            fetch(options.dataUrl)
                .then(response => response.json())
                .then(data => {
                    // console.table(data)

                    if (!data || data.length === 0) {
                        const li = document.createElement('li');
                        li.classList.add('text-danger');
                        li.textContent = options.noDataText || 'No hay datos disponibles';
                        list.innerHTML = '';
                        list.appendChild(li);
                        list.style.display = 'block';
                        return;
                    }

                    items = data;
                    const sugerencias = items.filter(item =>
                        item.bien.toLowerCase().includes(valor)
                    );
                    mostrarSugerencias(sugerencias);
                })
                .catch(error => {
                    console.error('Error al cargar datos:', error);
                    const li = document.createElement('li');
                    li.classList.add('text-danger');
                    li.textContent = 'Error al cargar datos';
                    list.innerHTML = '';
                    list.appendChild(li);
                    list.style.display = 'block';
                });
        }
    });

    // Ocultar sugerencias al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            ocultarSugerencias();
        }
    });

    // Función para cargar datos desde la API
    function cargarDatos() {
        return fetch(options.dataUrl)
            .then(response => response.json())
            .then(data => {
                if (!data || data.length === 0) {
                    console.warn('No hay datos disponibles');
                    items = [];
                } else {
                    items = data;
                }
            })
            .catch(error => {
                console.error('Error al cargar datos:', error);
                items = [];
            });
    }

    // Cargar datos al inicializar
    // cargarDatos().then(() => {
    //     console.log('Datos cargados al inicializar:', items);
    // });

    // Retornar funciones públicas
    return {
        ocultarSugerencias,
        mostrarSugerencias,
        seleccionarItem,
        recargarDatos: function() {
            cargarDatos().then(() => {
                console.log('Datos recargados:', items);
                // Disparar el evento input si hay texto en el campo
                if (input.value.trim() !== '') {
                    input.dispatchEvent(new Event('input'));
                }
            });
        },
        getItems: function() {
            return items;
        }
    };
}

// Ejemplos de uso:
/*
// Ejemplo básico
const autocomplete = initAutocompleteSearch('#search-container', {
    dataUrl: '/api/items/list'
});

// Ejemplo con opciones personalizadas
const autocompleteCustom = initAutocompleteSearch('#product-search', {
    dataUrl: '/api/products',
    dataKey: 'nombre',
    idKey: 'producto_id',
    hiddenInputSelector: '#product_id',
    onSelect: function(item) {
        console.log('Producto seleccionado:', item);
        // Acciones adicionales al seleccionar
    },
    noMatchText: 'No se encontraron productos'
});

// Para recargar datos
autocomplete.recargarDatos();
*/
