/**
 * Manejador de formularios para envío mediante AJAX simplificado
 * Esta función permite enviar cualquier formulario mediante AJAX y realizar acciones personalizadas
 * con la respuesta obtenida del servidor.
 *
 * @param {string} formSelector - Selector CSS para identificar el/los formulario(s) (ej: ".FormularioAjax")
 * @param {Object} options - Opciones de configuración
 * @param {Function} options.onBefore - Función llamada antes de enviar la petición, puede modificar o validar datos
 * @param {Function} options.onSuccess - Función llamada cuando el servidor responde exitosamente
 * @param {Function} options.onError - Función llamada cuando ocurre un error
 * @param {boolean} options.showConfirm - Mostrar diálogo de confirmación antes de enviar (default: false)
 * @param {string} options.confirmMessage - Mensaje de confirmación personalizado
 * @param {boolean} options.resetOnSuccess - Resetear el formulario después de éxito (default: false)
 * @param {boolean} options.closeModalOnSuccess - Cerrar modal asociado (el modal contenedor ha de tener la clase .modal) (default: false)
 * @param {string} options.redirectOnSuccess - URL para redireccionar después de éxito
 * @param {Object} options.headers - Headers HTTP adicionales para la petición
 * @param {string} options.contentType - Tipo de contenido a enviar (default: null, se usará FormData)
 * @param {Object|Function} options.customBody - Objeto o función que retorna el body personalizado
 * @param {string} options.forceMethod - Forzar método HTTP (por ejemplo: 'PUT', 'PATCH', 'DELETE')
 */
function inicializarFormularioAjax(formSelector, options = {}) {
    // Opciones por defecto
    const defaultOptions = {
        onBefore: null, // Nueva opción para ejecutar código antes del envío
        onSuccess: response => showToast(response),
        onError: error => showToast(error),
        showConfirm: false,
        confirmMessage: '¿Estás seguro de enviar este formulario?',
        resetOnSuccess: false,
        closeModalOnSuccess: false,
        redirectOnSuccess: null,
        headers: {}, // Headers personalizados
        contentType: null, // Tipo de contenido personalizado (ej: application/json)
        customBody: null, // Body personalizado (objeto o función)
        forceMethod: null // ← Nuevo: permite forzar el método HTTP (útil para PUT o PATCH)
    };

    // Combinar opciones por defecto con las proporcionadas
    const settings = { ...defaultOptions, ...options };

    // Obtener todos los formularios que coinciden con el selector
    const formularios = document.querySelectorAll(formSelector);

    // Asignar el evento submit a cada formulario
    formularios.forEach(formulario => {
        // Remover eventos anteriores si existieran (para evitar duplicados)
        formulario.removeEventListener('submit', formSubmitHandler);

        // Agregar el nuevo manejador de eventos
        formulario.addEventListener('submit', formSubmitHandler);
    });

    /**
     * Manejador del evento submit
     * @param {Event} e - Evento submit
     */
    function formSubmitHandler(e) {
        e.preventDefault();

        // Mostrar confirmación si está habilitado
        if (settings.showConfirm && !confirm(settings.confirmMessage)) {
            return; // El usuario canceló el envío
        }

        // Referencia al formulario actual
        const form = this;

        // ← Nuevo: si se define forceMethod, tiene prioridad sobre el método del formulario
        let method = settings.forceMethod
            ? settings.forceMethod.toUpperCase()
            : (form.getAttribute('method') || 'POST').toUpperCase();

        // URL destino
        let action = form.getAttribute('action');

        // Preparar headers base
        const headers = {
            'X-Requested-With': 'XMLHttpRequest', // Indica al servidor que es una petición AJAX
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            ...settings.headers
        };

        // Preparar el body según las opciones
        let body;

        // Si hay un customBody, usarlo
        if (settings.customBody) {
            if (typeof settings.customBody === 'function') {
                // Si es una función, ejecutarla pasando el formulario
                body = settings.customBody(form);
            } else {
                // Si es un objeto, usarlo directamente
                body = settings.customBody;
            }

            // Aplicar tipo de contenido si se especifica
            if (settings.contentType && !headers['Content-Type']) {
                headers['Content-Type'] = settings.contentType;

                // Si es JSON y el body no está serializado, convertirlo
                if (settings.contentType === 'application/json' && typeof body !== 'string') {
                    body = JSON.stringify(body);
                }
            }
        } else {
            // Por defecto usar FormData
            body = new FormData(form);
        }

        // Ejecutar callback onBefore si existe
        if (settings.onBefore && typeof settings.onBefore === 'function') {
            const beforeResult = settings.onBefore(form, { body, headers, method, action });

            // Si retorna false, cancelar envío
            if (beforeResult === false) {
                return;
            }

            // Si retorna objeto, permitir modificar configuración
            if (beforeResult && typeof beforeResult === 'object') {
                if (beforeResult.body !== undefined) body = beforeResult.body;
                if (beforeResult.headers !== undefined) Object.assign(headers, beforeResult.headers);
                if (beforeResult.method !== undefined) method = beforeResult.method;
                if (beforeResult.action !== undefined) action = beforeResult.action;
            }
        }

        // Configuración del fetch
        const fetchConfig = {
            method: method.toUpperCase(),
            headers,
            body,
            mode: 'cors',
            cache: 'no-cache'
        };

        // Mostrar indicador de carga
        toggleLoadingState(form, true);

        // Realizar la petición AJAX
        fetch(action, fetchConfig)
            .then(response => {
                // Intentar convertir la respuesta a JSON
                return response.json()
                    .catch(e => {
                        // Error al parsear JSON → posiblemente la ruta no existe o devuelve HTML
                        console.warn('No se pudo parsear el JSON. ' +
                                     'Es posible que la ruta no exista o devuelva HTML.\n', e);
                        return { success: false, message: 'Error al procesar la respuesta' };
                    });
            })
            .then(responseData => {
                // Si el servidor respondió con éxito
                if (responseData.success === true) {
                    // Ejecutar callback onSuccess
                    settings.onSuccess(responseData, form);

                    // Resetear formulario si está configurado
                    if (settings.resetOnSuccess) form.reset();

                    // Cerrar modal si corresponde
                    if (settings.closeModalOnSuccess) {
                        const modal = form.closest('.modal');
                        if (modal) {
                            modal.dataset.modalSaved = 'true';

                            if (typeof ocultarModal === 'function' && modal.id) {
                                ocultarModal(`#${modal.id}`);
                            } else {
                                modal.classList.remove("active");
                            }
                        }
                    }

                    // Redireccionar si se indicó
                    if (settings.redirectOnSuccess) {
                        window.location.href = settings.redirectOnSuccess;
                    }
                } else {
                    // En caso de error
                    settings.onError(responseData, form);

                    // Mostrar ruta faltante si existe
                    if (responseData.path)
                        console.error(`Revise que la ruta ${responseData.path} exista.\nError al enviar el formulario ${formSelector}`);
                }
            })
            .catch(error => {
                // Errores en la solicitud
                console.error('Error en la solicitud:', error);
                settings.onError(error, form);
            })
            .finally(() => {
                // Quitar indicador de carga
                toggleLoadingState(form, false);
            });
    }

    /**
     * Alterna el estado de carga en el formulario
     * @param {HTMLFormElement} form - Formulario
     * @param {boolean} isLoading - Estado de carga
     */
    function toggleLoadingState(form, isLoading) {
        // Deshabilitar/habilitar los campos del formulario
        const formElements = form.querySelectorAll('input, select, textarea, button');
        formElements.forEach(element => {
            element.disabled = isLoading;
        });

        // Buscar un botón de envío para mostrar el estado de carga
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            const originalText = submitButton.dataset.originalText || submitButton.innerHTML;

            if (isLoading) {
                // Guardar texto original si no está guardado
                if (!submitButton.dataset.originalText) {
                    submitButton.dataset.originalText = originalText;
                }
                // Cambiar a texto de carga
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            } else {
                // Restaurar texto original
                submitButton.innerHTML = originalText;
            }
        }
    }

    // Retornar la configuración actual para posibles usos externos
    return settings;
}
