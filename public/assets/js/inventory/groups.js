function initGroupFunctions() {
    // Inicializar formulario para crear grupo
    // ruta del form: /api/groups/create
    if (document.querySelector('#formCrearGrupo')) {
        inicializarFormularioAjax('#formCrearGrupo', {
            closeModalOnSuccess: true,
            resetOnSuccess: true,
            onSuccess: (response) => {
                refrescarVistaGrupos();
                showToast(response);
            }
        });
    }

    // Inicializar formulario para renombrar grupo
    // ruta del form: /api/groups/rename
    if (document.querySelector('#formRenombrarGrupo')) {
        inicializarFormularioAjax('#formRenombrarGrupo', {
            closeModalOnSuccess: true,
            onSuccess: (response) => {
                refrescarVistaGrupos();
                showToast(response);
            }
        });
    }

    // Inicializar búsqueda de grupos
    iniciarBusqueda('searchGroup');
    initPortalGroupDropdowns();

    console.log('Funciones de grupos inicializadas');
}

function btnRenombrarGrupo() {
    const id = selectedItem.id;
    const nombreActual = selectedItem.name;
    document.getElementById('grupoRenombrarId').value = id;
    document.getElementById('grupoRenombrarNombre').value = nombreActual;
    mostrarModal('#modalRenombrarGrupo');
}

// eliminarGrupo()
function btnEliminarGrupo() {
    const idGrupo = selectedItem.id;

    eliminarRegistro({
        url: `/api/groups/delete/${idGrupo}`,
        onSuccess: (response) => {
            refrescarVistaGrupos();
            showToast(response);
        }
    });
}

// ---------------------------------------------------------------------
// REFRESCAR LA VISTA DE GRUPOS SIN RECARGAR TODA LA PÁGINA
// ---------------------------------------------------------------------
async function refrescarVistaGrupos() {
    loadContent('/groups', {
        containerSelector: '.content',
        updateHistory: false,
        onSuccess: () => {
            initGroupFunctions();
        }
    });
}

function initPortalGroupDropdowns() {
    const dropdowns = document.querySelectorAll('[data-sede-dropdown]');
    const searchInput = document.getElementById('searchGroup');

    if (!dropdowns.length || !searchInput) {
        return;
    }

    const controllers = Array.from(dropdowns).map((dropdown) => ({
        dropdown,
        controller: createSedeDropdownController(dropdown, '.inventory-sede-body'),
    }));

    const updateDropdownState = () => {
        const hasFilter = searchInput.value.trim().length > 0;

        controllers.forEach(({ dropdown, controller }) => {
            const cards = dropdown.querySelectorAll('.card-item');
            const visibleCards = Array.from(cards).filter((card) => card.style.display !== 'none');
            const visibleCountBadge = dropdown.querySelector('[data-visible-count]');
            const emptyByFilterMessage = dropdown.querySelector('[data-sede-empty]');

            if (visibleCountBadge) {
                visibleCountBadge.textContent = String(visibleCards.length);
            }

            if (emptyByFilterMessage) {
                emptyByFilterMessage.classList.toggle('hidden', visibleCards.length > 0);
            }

            if (hasFilter) {
                controller.setOpen(visibleCards.length > 0, true);
            } else {
                controller.setOpen(false, true);
            }
        });
    };

    ['keyup', 'input', 'search'].forEach((eventName) => {
        searchInput.addEventListener(eventName, updateDropdownState);
    });
    updateDropdownState();
}

function createSedeDropdownController(dropdown, bodySelector) {
    if (dropdown.__sedeAccordionController) {
        return dropdown.__sedeAccordionController;
    }

    const summary = dropdown.querySelector('summary');
    const body = dropdown.querySelector(bodySelector);

    if (!summary || !body) {
        const fallbackController = {
            setOpen: (shouldOpen) => {
                dropdown.open = shouldOpen;
            }
        };
        dropdown.__sedeAccordionController = fallbackController;
        return fallbackController;
    }

    let animation = null;
    let isClosing = false;
    let isExpanding = false;

    const onAnimationFinish = (open) => {
        dropdown.open = open;
        dropdown.style.height = '';
        dropdown.style.overflow = '';
        animation = null;
        isClosing = false;
        isExpanding = false;
    };

    const animateOpen = (animate = true) => {
        if (dropdown.open && !isClosing) {
            return;
        }

        dropdown.style.overflow = 'hidden';
        dropdown.style.height = `${dropdown.offsetHeight}px`;
        dropdown.open = true;

        window.requestAnimationFrame(() => {
            const startHeight = `${dropdown.offsetHeight}px`;
            const endHeight = `${summary.offsetHeight + body.offsetHeight}px`;

            if (animation) {
                animation.cancel();
            }

            if (!animate || typeof dropdown.animate !== 'function') {
                onAnimationFinish(true);
                return;
            }

            isExpanding = true;
            animation = dropdown.animate(
                { height: [startHeight, endHeight] },
                { duration: 520, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' }
            );
            animation.onfinish = () => onAnimationFinish(true);
            animation.oncancel = () => {
                isExpanding = false;
            };
        });
    };

    const animateClose = (animate = true) => {
        if (!dropdown.open && !isExpanding) {
            return;
        }

        const startHeight = `${dropdown.offsetHeight}px`;
        const endHeight = `${summary.offsetHeight}px`;

        if (animation) {
            animation.cancel();
        }

        if (!animate || typeof dropdown.animate !== 'function') {
            onAnimationFinish(false);
            return;
        }

        isClosing = true;
        animation = dropdown.animate(
            { height: [startHeight, endHeight] },
            { duration: 460, easing: 'cubic-bezier(0.4, 0, 1, 1)' }
        );
        animation.onfinish = () => onAnimationFinish(false);
        animation.oncancel = () => {
            isClosing = false;
        };
    };

    summary.addEventListener('click', (event) => {
        event.preventDefault();

        if (isClosing || !dropdown.open) {
            animateOpen(true);
        } else {
            animateClose(true);
        }
    });

    const controller = {
        setOpen: (shouldOpen, animate = true) => {
            if (shouldOpen) {
                animateOpen(animate);
            } else {
                animateClose(animate);
            }
        }
    };

    dropdown.__sedeAccordionController = controller;
    return controller;
}
