/**
 * modal.js - Utilidades para manejo de ventanas modales
 *
 * Este archivo contiene funciones para gestionar el comportamiento de modales en la aplicación.
 * Proporciona funcionalidades para mostrar y ocultar modales, así como para detectar clics fuera
 * del contenido del modal para cerrarlos automáticamente.
 *
 * Reglas de comportamiento:
 * - El modal se cierra solo cuando el clic comienza Y termina fuera del modal
 * - El modal NO se cierra si el clic comienza dentro del modal, incluso si termina fuera
 * - El modal NO se cierra si el clic comienza fuera pero termina dentro del modal
 * - Al abrir un modal, se enfoca automáticamente el primer campo de entrada visible
 *
 * Esta implementación evita problemas comunes como el cierre accidental durante la selección
 * de texto o al arrastrar elementos dentro del modal.
 *
 * @version 3.2
 * @date 2025-12-07
 */

// Variable global para cada modal
const modales = new Map();

/**
 * Muestra un modal y configura sus event listeners
 * @param {string} selectorModal - Selector CSS del modal a mostrar
 */
function mostrarModal(selectorModal) {
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/f957b755-7501-40b7-8431-7950194dd570',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'e8cd93'},body:JSON.stringify({sessionId:'e8cd93',runId:'pre-fix',hypothesisId:'H1_H2_H3',location:'public/assets/js/helpers/modal.js:mostrarModal:entry',message:'mostrarModal() llamado',data:{selectorModal},timestamp:Date.now()})}).catch(()=>{});
    // #endregion agent log
    const modal = document.querySelector(selectorModal);
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/f957b755-7501-40b7-8431-7950194dd570',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'e8cd93'},body:JSON.stringify({sessionId:'e8cd93',runId:'pre-fix',hypothesisId:'H1',location:'public/assets/js/helpers/modal.js:mostrarModal:lookup',message:'Resultado querySelector del modal',data:{selectorModal,found:!!modal,foundId:modal?.id,foundClass:modal?.className},timestamp:Date.now()})}).catch(()=>{});
    // #endregion agent log
    if (!modal) return;

    // Mostramos el modal
    modal.classList.add("active");
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/f957b755-7501-40b7-8431-7950194dd570',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'e8cd93'},body:JSON.stringify({sessionId:'e8cd93',runId:'pre-fix',hypothesisId:'H3',location:'public/assets/js/helpers/modal.js:mostrarModal:active',message:'Clase active agregada al modal',data:{selectorModal,hasActive:modal.classList.contains('active')},timestamp:Date.now()})}).catch(()=>{});
    // #endregion agent log

    // Deshabilitar la deselección mientras el modal está abierto
    // Esto maneja la funcionalidad en selection.js
    allowDeselection = false;

    // Datos del estado del clic para este modal
    const estadoModal = {
        clickIniciadoDentro: false,
        modalEl: modal
    };

    // Función para detectar cuando inicia un clic
    const mouseDownHandler = function(e) {
        // Verificamos si el clic inició dentro del contenido del modal
        const modalContent = modal.querySelector('.modal-content') || modal.firstElementChild;
        estadoModal.clickIniciadoDentro = modalContent ? modalContent.contains(e.target) : modal.contains(e.target);
    };

    // Función para manejar el final del clic
    const mouseUpHandler = function(e) {
        // Solo cerramos si:
        // 1. El clic terminó sobre el fondo del modal (no sobre el contenido)
        // 2. El clic NO inició dentro del modal
        // 3. El clic NO terminó dentro del contenido del modal

        const modalContent = modal.querySelector('.modal-content') || modal.firstElementChild;
        const clickTerminoEnContenido = modalContent ? modalContent.contains(e.target) : false;

        if (e.target === modal && !estadoModal.clickIniciadoDentro && !clickTerminoEnContenido) {
            ocultarModal(selectorModal);
        }
    };

    // Almacenamos los handlers para poder eliminarlos después
    estadoModal.mouseDownHandler = mouseDownHandler;
    estadoModal.mouseUpHandler = mouseUpHandler;

    // Agregamos los event listeners
    document.addEventListener("mousedown", mouseDownHandler);
    document.addEventListener("mouseup", mouseUpHandler);

    // Guardamos la referencia en nuestro Map
    modales.set(selectorModal, estadoModal);

    // Enfocar el primer input visible dentro del modal
    const firstInput = modal.querySelector('input:not([type="hidden"])');

    // console.log('Modal encontrado:', modal); // Debug: Verificar el modal seleccionado
    // console.log('Primer input visible:', firstInput); // Debug: Verificar el input seleccionado
    if (firstInput) {
        setTimeout(() => {
            firstInput.focus();
        }, 80); // Usar un timeout para asegurar que el modal esté completamente visible
    }
}

/**
 * Oculta un modal y limpia sus event listeners
 * @param {string} selectorModal - Selector CSS del modal a ocultar
 */
function ocultarModal(selectorModal) {
    const modal = document.querySelector(selectorModal);
    if (!modal) return;

    // Ocultamos el modal
    modal.classList.remove("active");

    // Obtenemos los handlers guardados
    const estadoModal = modales.get(selectorModal);
    if (estadoModal) {
        // Removemos los event listeners
        document.removeEventListener("mousedown", estadoModal.mouseDownHandler);
        document.removeEventListener("mouseup", estadoModal.mouseUpHandler);

        // Eliminamos la referencia del Map
        modales.delete(selectorModal);
    }

    // Volver a permitir la deselección cuando se cierra el modal
    // allowDeselection = true;

    // Retrasar la reactivación de la deselección para evitar que
    // el mismo clic que cierra el modal cause una deselección
    if (typeof allowDeselection !== 'undefined') {
        setTimeout(() => {
            allowDeselection = true;
        }, 100); // Pequeño retraso de 100ms
    }
}
