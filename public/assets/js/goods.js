// Función que inicializa el formulario para crear bienes
function initFormsBien() {
    if (document.querySelector('#formCrearBien')) {

// Inicializa el formulario con Ajax para que se envíe la información al servidor sin recargar la página
        inicializarFormularioAjax('#formCrearBien', {

// Permite que el formulario se limpie cuando sea enviado con éxito
            resetOnSuccess: true,

// Cierra el formulario cuando el proceso es ejecutado con éxito
            closeModalOnSuccess: true,

// Define las acciones a realizar cuando el servidor responda con éxito
            onSuccess: (response) => {

// Muestra mensaje emergente de éxito
                showToast(response);

// Se vuelve a llamar a sí misma la función, esto para recuperar la conexión de algunos botones o formularios que se pierden
                loadContent('/goods', {
                    onSuccess: () => initFormsBien()
                });
            }
        });
    }

// Función que inicia el formulario para editar bienes
    if (document.querySelector('#formActualizarBien')) {
        inicializarFormularioAjax('#formActualizarBien', {
            closeModalOnSuccess: true,
            onSuccess: (response) => {
                showToast(response);
                loadContent('/goods', {
                    onSuccess: () => initFormsBien()
                });
            }
        });
    }

// Inicializa la lógica de la barra de búsqueda
    iniciarBusqueda('searchGood');

// Inicializa la lógica de los menú desplegables por sede
    initPortalGoodsDropdowns();
}

// Inicializa el formulario para eliminar
function eliminarBien(id) {

// Función que elimina el registro con el id seleccionado
    eliminarRegistro({
        url: `/api/goods/delete/${id}`,
        onSuccess: (response) => {
            loadContent('/goods', {
                onSuccess: () => initFormsBien()
            });
            showToast(response);
        }
    });
}

// Función ejecutada cuando el usuario da clic en el botón "Editar" de un bien en específico
function btnEditarBien(id, nombre) {

// Obtiene el id del elemento que se va a editar
    document.getElementById("actualizarId").value = id;

// Obtiene el nombre del elemento que se va a editar
    document.getElementById("actualizarNombreBien").value = nombre;

// limpia el campo "Imagen" para que no quede cargada la imagen anterior
    document.getElementById("actualizarImagenBien").value = "";

// Función que muestra el modal
    mostrarModal('#modalActualizarBien')
}


/**
 * Inicializa el comportamiento de los dropdowns en la vista de bienes.
 * Configura la interacción con el buscador, el conteo de elementos visibles
 * y la apertura/cierre dinámico de cada dropdown.
 */
function initPortalGoodsDropdowns() {

    /**
     * Obtiene todos los elementos del DOM que tienen el atributo
     * data-sede-dropdown, es decir, los dropdowns de sedes.
     */
    const dropdowns = document.querySelectorAll('[data-sede-dropdown]');

    /**
     * Obtiene el campo de búsqueda mediante su id "searchGood".
     */
    const searchInput = document.getElementById('searchGood');

    /**
     * Si no existen dropdowns o no existe el input de búsqueda,
     * se detiene la ejecución de la función.
     */
    if (!dropdowns.length || !searchInput) {
        return;
    }

    /**
     * Crea un arreglo de objetos a partir de los dropdowns encontrados.
     * Cada objeto contiene:
     * - dropdown: el elemento HTML del dropdown
     * - controller: el controlador que gestiona su apertura/cierre
     */
    const controllers = Array.from(dropdowns).map((dropdown) => ({
        
        // Referencia directa al elemento dropdown
        dropdown,

        /**
         * Controlador del dropdown.
         * Se crea utilizando el selector ".goods-sede-body" para ubicar
         * el contenido interno del desplegable.
         */
        controller: createSedeDropdownController(dropdown, '.goods-sede-body'),
    }));

    /**
     * Función que actualiza el estado visual de todos los dropdowns.
     * Se encarga de:
     * - Detectar si hay texto en el buscador
     * - Contar elementos visibles
     * - Mostrar/ocultar mensajes
     * - Abrir o cerrar dropdowns automáticamente
     */
    const updateDropdownState = () => {

        /**
         * Determina si el usuario ha ingresado texto en el buscador.
         * trim() elimina espacios innecesarios.
         */
        const hasFilter = searchInput.value.trim().length > 0;

        /**
         * Recorre cada dropdown junto con su controlador.
         */
        controllers.forEach(({ dropdown, controller }) => {

            /**
             * Obtiene todas las tarjetas dentro del dropdown actual.
             */
            const cards = dropdown.querySelectorAll('.card-item');

            /**
             * Filtra únicamente las tarjetas visibles.
             * Se consideran visibles aquellas cuyo display no es 'none'.
             */
            const visibleCards = Array.from(cards).filter(
                (card) => card.style.display !== 'none'
            );

            /**
             * Obtiene el elemento que muestra el número de tarjetas visibles.
             */
            const visibleCountBadge = dropdown.querySelector('[data-visible-count]');

            /**
             * Obtiene el elemento que muestra el mensaje cuando no hay resultados.
             */
            const emptyByFilterMessage = dropdown.querySelector('[data-sede-empty]');

            /**
             * Si existe el contador visual, actualiza su contenido
             * con la cantidad de tarjetas visibles.
             */
            if (visibleCountBadge) {
                visibleCountBadge.textContent = String(visibleCards.length);
            }

            /**
             * Si existe el mensaje de "sin resultados",
             * se muestra o se oculta según haya elementos visibles.
             */
            if (emptyByFilterMessage) {
                emptyByFilterMessage.classList.toggle(
                    'hidden',
                    visibleCards.length > 0
                );
            }

            /**
             * Controla la apertura/cierre del dropdown:
             * - Si hay búsqueda activa:
             *     Se abre solo si tiene elementos visibles.
             * - Si no hay búsqueda:
             *     Se cierra el dropdown.
             */
            if (hasFilter) {
                controller.setOpen(visibleCards.length > 0, true);
            } else {
                controller.setOpen(false, true);
            }
        });
    };

    /**
     * Asocia la función updateDropdownState a los eventos del input de búsqueda.
     * Esto permite que la interfaz responda en tiempo real mientras el usuario interactúa.
     */
    ['keyup', 'input', 'search'].forEach((eventName) => {
        searchInput.addEventListener(eventName, updateDropdownState);
    });

    /**
     * Ejecuta una actualización inicial para asegurar que el estado
     * de los dropdowns sea correcto al cargar la vista.
     */
    updateDropdownState();
}

/**
 * Crea y devuelve un controlador para manejar la apertura y cierre
 * de un dropdown de sede, con soporte para animaciones suaves.
 *
 * @param {HTMLElement} dropdown - Elemento HTML principal del dropdown.
 * @param {string} bodySelector - Selector CSS que identifica el cuerpo interno del dropdown.
 * @returns {Object} Controlador con el método setOpen(shouldOpen, animate).
 */
function createSedeDropdownController(dropdown, bodySelector) {

    /**
     * Si el dropdown ya tiene un controlador asociado,
     * lo reutiliza para evitar crearlo nuevamente.
     */
    if (dropdown.__sedeAccordionController) {
        return dropdown.__sedeAccordionController;
    }

    /**
     * Obtiene el elemento <summary>, que actúa como encabezado clicable del dropdown.
     */
    const summary = dropdown.querySelector('summary');

    /**
     * Obtiene el cuerpo interno del dropdown usando el selector recibido.
     */
    const body = dropdown.querySelector(bodySelector);

    /**
     * Si no existe el summary o el cuerpo del dropdown,
     * se crea un controlador alternativo básico sin animaciones.
     * Este controlador únicamente abre o cierra el dropdown
     * modificando directamente la propiedad open.
     */
    if (!summary || !body) {
        const fallbackController = {
            setOpen: (shouldOpen) => {
                dropdown.open = shouldOpen;
            }
        };

        /**
         * Guarda el controlador alternativo en el dropdown
         * para reutilizarlo más adelante.
         */
        dropdown.__sedeAccordionController = fallbackController;
        return fallbackController;
    }

    /**
     * Variable que almacena la animación actual del dropdown.
     * Inicialmente no existe ninguna animación en curso.
     */
    let animation = null;

    /**
     * Indica si el dropdown se está cerrando actualmente.
     */
    let isClosing = false;

    /**
     * Indica si el dropdown se está expandiendo actualmente.
     */
    let isExpanding = false;

    /**
     * Función que se ejecuta cuando una animación termina.
     * Se encarga de:
     * - establecer el estado final abierto/cerrado
     * - limpiar estilos temporales
     * - reiniciar variables de control
     *
     * @param {boolean} open - Estado final del dropdown.
     */
    const onAnimationFinish = (open) => {
        dropdown.open = open;
        dropdown.style.height = '';
        dropdown.style.overflow = '';
        animation = null;
        isClosing = false;
        isExpanding = false;
    };

    /**
     * Función que abre el dropdown, con o sin animación.
     *
     * @param {boolean} animate - Indica si debe animarse la apertura.
     */
    const animateOpen = (animate = true) => {

        /**
         * Si el dropdown ya está abierto y no se está cerrando,
         * no es necesario ejecutar nuevamente la apertura.
         */
        if (dropdown.open && !isClosing) {
            return;
        }

        /**
         * Oculta el desbordamiento para evitar que el contenido
         * se salga visualmente durante la animación.
         */
        dropdown.style.overflow = 'hidden';

        /**
         * Fija la altura inicial actual del dropdown antes de animar.
         */
        dropdown.style.height = `${dropdown.offsetHeight}px`;

        /**
         * Marca el dropdown como abierto para permitir medir
         * correctamente la altura final del contenido.
         */
        dropdown.open = true;

        /**
         * requestAnimationFrame espera al siguiente ciclo de renderizado
         * del navegador para iniciar la animación con medidas correctas.
         */
        window.requestAnimationFrame(() => {

            /**
             * Altura inicial del dropdown antes de expandirse.
             */
            const startHeight = `${dropdown.offsetHeight}px`;

            /**
             * Altura final del dropdown al estar completamente abierto.
             * Se calcula sumando la altura del summary y la del body.
             */
            const endHeight = `${summary.offsetHeight + body.offsetHeight}px`;

            /**
             * Si existe una animación previa, la cancela antes de iniciar otra.
             */
            if (animation) {
                animation.cancel();
            }

            /**
             * Si no se desea animación o el navegador no soporta
             * el método animate(), se completa la apertura inmediatamente.
             */
            if (!animate || typeof dropdown.animate !== 'function') {
                onAnimationFinish(true);
                return;
            }

            /**
             * Marca que el dropdown está en proceso de expansión.
             */
            isExpanding = true;

            /**
             * Ejecuta la animación de apertura modificando la altura.
             */
            animation = dropdown.animate(
                { height: [startHeight, endHeight] },
                { duration: 520, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' }
            );

            /**
             * Cuando la animación termina correctamente,
             * se limpian estados y estilos.
             */
            animation.onfinish = () => onAnimationFinish(true);

            /**
             * Si la animación es cancelada, se actualiza el estado interno.
             */
            animation.oncancel = () => {
                isExpanding = false;
            };
        });
    };

    /**
     * Función que cierra el dropdown, con o sin animación.
     *
     * @param {boolean} animate - Indica si debe animarse el cierre.
     */
    const animateClose = (animate = true) => {

        /**
         * Si el dropdown ya está cerrado y no se encuentra expandiéndose,
         * no es necesario ejecutar el cierre.
         */
        if (!dropdown.open && !isExpanding) {
            return;
        }

        /**
         * Altura inicial del dropdown antes de cerrarse.
         */
        const startHeight = `${dropdown.offsetHeight}px`;

        /**
         * Altura final del dropdown cerrado.
         * Solo se deja visible el summary.
         */
        const endHeight = `${summary.offsetHeight}px`;

        /**
         * Si existe una animación previa, se cancela antes de iniciar otra.
         */
        if (animation) {
            animation.cancel();
        }

        /**
         * Si no se desea animación o el navegador no soporta animate(),
         * se completa el cierre inmediatamente.
         */
        if (!animate || typeof dropdown.animate !== 'function') {
            onAnimationFinish(false);
            return;
        }

        /**
         * Marca que el dropdown está en proceso de cierre.
         */
        isClosing = true;

        /**
         * Ejecuta la animación de cierre reduciendo la altura.
         */
        animation = dropdown.animate(
            { height: [startHeight, endHeight] },
            { duration: 460, easing: 'cubic-bezier(0.4, 0, 1, 1)' }
        );

        /**
         * Cuando la animación termina correctamente,
         * se actualiza el estado final del dropdown.
         */
        animation.onfinish = () => onAnimationFinish(false);

        /**
         * Si la animación se cancela, se limpia el indicador de cierre.
         */
        animation.oncancel = () => {
            isClosing = false;
        };
    };

    /**
     * Intercepta el clic sobre el summary para sustituir
     * el comportamiento nativo del dropdown por uno controlado
     * mediante animaciones personalizadas.
     */
    summary.addEventListener('click', (event) => {
        event.preventDefault();

        /**
         * Si está cerrándose o actualmente está cerrado,
         * se ejecuta la apertura.
         * En caso contrario, se ejecuta el cierre.
         */
        if (isClosing || !dropdown.open) {
            animateOpen(true);
        } else {
            animateClose(true);
        }
    });

    /**
     * Controlador principal del dropdown.
     * Expone el método setOpen para abrir o cerrar
     * el desplegable desde otras partes del código.
     */
    const controller = {
        /**
         * Abre o cierra el dropdown según el valor recibido.
         *
         * @param {boolean} shouldOpen - Indica si el dropdown debe abrirse.
         * @param {boolean} animate - Indica si la acción debe llevar animación.
         */
        setOpen: (shouldOpen, animate = true) => {
            if (shouldOpen) {
                animateOpen(animate);
            } else {
                animateClose(animate);
            }
        }
    };

    /**
     * Guarda el controlador en el propio dropdown
     * para evitar recrearlo en futuras llamadas.
     */
    dropdown.__sedeAccordionController = controller;

    /**
     * Devuelve el controlador creado.
     */
    return controller;
}
