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

    // Inicializar busqueda de grupos, inventarios y bienes
    initPortalGroupDropdowns();
    initGroupSearch();

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

function initGroupSearch() {
    const root = document.querySelector('[data-group-search-root]');
    const searchForm = document.getElementById('groupSearchForm');
    const searchInput = document.getElementById('searchGroup');
    const modeSelect = document.getElementById('groupSearchMode');
    const resultsContainer = document.querySelector('[data-group-search-results]');
    const groupListing = document.querySelector('[data-group-listing]');

    if (!searchInput || !modeSelect || !resultsContainer || !groupListing) {
        return;
    }

    if (searchInput.__groupSearchController) {
        searchInput.__groupSearchController.apply();
        return searchInput.__groupSearchController;
    }

    const portalCatalog = root?.dataset.portalCatalog === '1';
    const placeholders = {
        groups: 'Buscar grupo...',
        inventories: 'Buscar inventario...',
        goods: 'Buscar bien...',
    };
    const emptyMessages = {
        inventories: 'No se encontraron inventarios.',
        goods: 'No se encontraron bienes.',
    };
    const validModes = ['groups', 'inventories', 'goods'];

    let debounceTimer = null;
    let currentRequest = null;

    const buildSearchStateUrl = () => {
        const url = new URL(searchForm?.getAttribute('action') || '/groups', window.location.origin);
        const params = new URLSearchParams();
        const mode = validModes.includes(modeSelect.value) ? modeSelect.value : 'groups';
        const term = searchInput.value.trim();

        if (portalCatalog) {
            params.set('portal', '1');
        }

        if (mode !== 'groups' || term !== '') {
            params.set('search_type', mode);
        }

        if (term !== '') {
            params.set('search', term);
        }

        url.search = params.toString();
        return `${url.pathname}${url.search}`;
    };

    const syncSearchRoute = () => {
        const url = buildSearchStateUrl();
        window.history.replaceState({ url }, '', url);
        return url;
    };

    const hydrateSearchFromRoute = () => {
        const params = new URLSearchParams(window.location.search);
        const mode = params.get('search_type');

        if (validModes.includes(mode)) {
            modeSelect.value = mode;
        }

        if (params.has('search')) {
            searchInput.value = params.get('search') || '';
        }
    };

    const updatePortalDropdowns = () => {
        if (typeof searchInput.__portalGroupDropdownUpdater === 'function') {
            searchInput.__portalGroupDropdownUpdater();
        }
    };

    const clearPendingRemoteSearch = () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = null;

        if (currentRequest) {
            currentRequest.abort();
            currentRequest = null;
        }
    };

    const setListingVisible = (visible) => {
        groupListing.classList.toggle('hidden', !visible);
    };

    const clearResults = () => {
        resultsContainer.innerHTML = '';
        resultsContainer.classList.add('hidden');
    };

    const resetGroupCards = () => {
        document.querySelectorAll('[data-group-card]').forEach((card) => {
            card.style.display = '';
        });
        updatePortalDropdowns();
    };

    const applyLocalGroupSearch = () => {
        syncSearchRoute();
        const filter = searchInput.value.toLowerCase().trim();

        document.querySelectorAll('[data-group-card]').forEach((card) => {
            const text = card.querySelector('.name-item')?.textContent?.toLowerCase() ?? '';
            card.style.display = text.includes(filter) ? '' : 'none';
        });

        clearResults();
        setListingVisible(true);
        updatePortalDropdowns();
    };

    const renderMessage = (message, iconClass = 'fa-circle-info') => {
        resultsContainer.classList.remove('hidden');
        resultsContainer.innerHTML = `
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-4 text-sm text-slate-600 shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="fas ${iconClass} text-emerald-600"></i>
                    <span>${escapeHtml(message)}</span>
                </div>
            </div>
        `;
    };

    const renderLoading = () => {
        renderMessage('Buscando...', 'fa-spinner fa-spin');
    };

    const renderError = () => {
        renderMessage('No se pudo completar la busqueda.', 'fa-triangle-exclamation');
    };

    const renderResults = (results, mode) => {
        resultsContainer.classList.remove('hidden');

        if (!results.length) {
            renderMessage(emptyMessages[mode] ?? 'No se encontraron resultados.');
            return;
        }

        resultsContainer.innerHTML = `
            <div class="grid gap-3">
                ${results.map(renderResultCard).join('')}
            </div>
        `;

        resultsContainer.querySelectorAll('[data-go-url]').forEach((button) => {
            button.addEventListener('click', () => {
                const url = button.dataset.goUrl;
                const resultType = button.dataset.resultType;
                const updateHistory = button.dataset.updateHistory !== 'false';
                const initializer = resultType === 'good'
                    ? 'initGoodsInventoryFunctions'
                    : 'initInventoryFunctions';

                if (typeof loadContent === 'function') {
                    loadContent(url, {
                        updateHistory,
                        onSuccess: () => {
                            if (typeof window[initializer] === 'function') {
                                window[initializer]();
                            }
                        },
                    });
                    return;
                }

                window.location.assign(url);
            });
        });
    };

    const runRemoteSearch = async () => {
        const mode = modeSelect.value;
        const term = searchInput.value.trim();

        syncSearchRoute();

        if (mode === 'groups') {
            applyLocalGroupSearch();
            return;
        }

        clearPendingRemoteSearch();

        if (term === '') {
            clearResults();
            resetGroupCards();
            setListingVisible(true);
            return;
        }

        setListingVisible(false);
        renderLoading();

        const request = new AbortController();
        currentRequest = request;
        const params = new URLSearchParams({
            type: mode,
            q: term,
        });

        if (portalCatalog) {
            params.set('portal', '1');
        }

        try {
            const response = await fetch(`/api/groups/search?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: request.signal,
            });

            if (!response.ok) {
                throw new Error('Error al buscar');
            }

            const data = await response.json();
            renderResults(Array.isArray(data.results) ? data.results : [], mode);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error(error);
            renderError();
        } finally {
            if (currentRequest === request) {
                currentRequest = null;
            }
        }
    };

    const scheduleRemoteSearch = () => {
        syncSearchRoute();
        window.clearTimeout(debounceTimer);
        if (currentRequest) {
            currentRequest.abort();
            currentRequest = null;
        }
        debounceTimer = window.setTimeout(runRemoteSearch, 250);
    };

    const applySearchMode = () => {
        const mode = modeSelect.value;
        searchInput.placeholder = placeholders[mode] ?? placeholders.groups;
        syncSearchRoute();

        if (mode === 'groups') {
            clearPendingRemoteSearch();
            applyLocalGroupSearch();
            return;
        }

        scheduleRemoteSearch();
    };

    const handleSearchInput = () => {
        syncSearchRoute();

        if (modeSelect.value === 'groups') {
            clearPendingRemoteSearch();
            applyLocalGroupSearch();
            return;
        }

        scheduleRemoteSearch();
    };

    searchInput.addEventListener('input', handleSearchInput);
    searchInput.addEventListener('search', handleSearchInput);
    modeSelect.addEventListener('change', applySearchMode);

    searchInput.__groupSearchController = {
        apply: applySearchMode,
        handleInput: handleSearchInput,
    };

    hydrateSearchFromRoute();
    applySearchMode();

    return searchInput.__groupSearchController;
}

function renderResultCard(result) {
    const resultType = result.type || 'inventory';
    const iconClass = {
        group: 'fa-layer-group',
        inventory: 'fa-folder',
        good: 'fa-box',
    }[resultType] ?? 'fa-search';
    const badge = {
        group: 'Grupo',
        inventory: 'Inventario',
        good: 'Bien',
    }[resultType] ?? 'Resultado';
    const metaParts = buildResultMeta(result);

    return `
        <article class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="break-words text-base font-semibold text-slate-800">${escapeHtml(result.title)}</h3>
                        <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">${badge}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">${metaParts.join(' &middot; ')}</p>
                </div>
            </div>
            <button
                type="button"
                class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                data-go-url="${escapeHtml(result.url)}"
                data-result-type="${escapeHtml(resultType)}"
                data-update-history="${result.update_history === false ? 'false' : 'true'}"
            >
                <i class="fas fa-arrow-right text-xs"></i>
                <span>Ir</span>
            </button>
        </article>
    `;
}

function buildResultMeta(result) {
    const meta = [];

    if (result.type === 'good') {
        meta.push(`Inventario: ${escapeHtml(result.inventory_name)}`);
        meta.push(`Grupo: ${escapeHtml(result.group_name)}`);
    } else if (result.type === 'inventory') {
        meta.push(`Grupo: ${escapeHtml(result.group_name)}`);
    } else if (result.type === 'group') {
        const count = Number(result.inventories_count ?? 0);
        meta.push(`${count} inventario${count === 1 ? '' : 's'}`);
    }

    if (result.sede_name) {
        meta.push(`Sede: ${escapeHtml(result.sede_name)}`);
    }

    if (result.asset_type) {
        meta.push(`Tipo: ${escapeHtml(result.asset_type)}`);
    }

    return meta;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function initPortalGroupDropdowns() {
    const dropdowns = document.querySelectorAll('[data-sede-dropdown]');
    const searchInput = document.getElementById('searchGroup');

    if (!dropdowns.length || !searchInput) {
        return () => {};
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

    searchInput.__portalGroupDropdownUpdater = updateDropdownState;

    if (searchInput.dataset.portalGroupDropdownsBound !== '1') {
        ['input', 'search'].forEach((eventName) => {
            searchInput.addEventListener(eventName, updateDropdownState);
        });
        searchInput.dataset.portalGroupDropdownsBound = '1';
    }

    updateDropdownState();

    return updateDropdownState;
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

function bootGroupSearchModule() {
    if (!document.querySelector('[data-group-search-root]')) {
        return null;
    }

    initPortalGroupDropdowns();
    return initGroupSearch();
}

window.initGroupFunctions = initGroupFunctions;
window.initGroupSearch = initGroupSearch;

(() => {
    if (window.__groupSearchAutoBootInstalled) {
        return;
    }

    window.__groupSearchAutoBootInstalled = true;

    const boot = () => {
        bootGroupSearchModule();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    document.addEventListener('input', (event) => {
        if (event.target?.id !== 'searchGroup') {
            return;
        }

        const controller = bootGroupSearchModule();
        controller?.handleInput?.();
    }, true);

    document.addEventListener('change', (event) => {
        if (event.target?.id !== 'groupSearchMode') {
            return;
        }

        const controller = bootGroupSearchModule();
        controller?.apply?.();
    }, true);

    const content = document.getElementById('main-content');
    if (content && typeof MutationObserver === 'function') {
        const observer = new MutationObserver(() => {
            boot();
        });
        observer.observe(content, { childList: true, subtree: true });
    }
})();
