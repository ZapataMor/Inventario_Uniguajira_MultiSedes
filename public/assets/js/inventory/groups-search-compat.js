(() => {
    if (window.groupSearchCompatibilityBound) {
        return;
    }

    window.groupSearchCompatibilityBound = true;

    let timer = null;
    let request = null;

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const elements = () => {
        const root = document.querySelector('[data-group-search-root]');
        const input = document.getElementById('searchGroup');
        const mode = document.getElementById('groupSearchMode');
        const results = document.querySelector('[data-group-search-results]');
        const listing = document.querySelector('[data-group-listing]');

        return root && input && mode && results && listing
            ? { root, input, mode, results, listing }
            : null;
    };

    const updateSedeDropdowns = () => {
        const input = document.getElementById('searchGroup');
        const hasFilter = input && input.value.trim().length > 0;

        document.querySelectorAll('[data-sede-dropdown]').forEach((dropdown) => {
            const visibleCards = Array.from(dropdown.querySelectorAll('.card-item'))
                .filter((card) => card.style.display !== 'none');
            const count = dropdown.querySelector('[data-visible-count]');
            const empty = dropdown.querySelector('[data-sede-empty]');

            if (count) {
                count.textContent = String(visibleCards.length);
            }

            if (empty) {
                empty.classList.toggle('hidden', visibleCards.length > 0);
            }

            dropdown.open = Boolean(hasFilter && visibleCards.length > 0);
        });
    };

    const clearResults = (ctx) => {
        ctx.results.innerHTML = '';
        ctx.results.classList.add('hidden');
    };

    const setListingVisible = (ctx, visible) => {
        ctx.listing.classList.toggle('hidden', !visible);
    };

    const showMessage = (ctx, message) => {
        ctx.results.classList.remove('hidden');
        ctx.results.innerHTML = `
            <div class="card">
                <div class="card-left">
                    <i class="fas fa-circle-info icon-folder"></i>
                </div>
                <div class="card-center">
                    <div class="title">${escapeHtml(message)}</div>
                </div>
            </div>
        `;
    };

    const applyLocalGroupSearch = (ctx) => {
        const filter = ctx.input.value.toLowerCase().trim();

        document.querySelectorAll('[data-group-card]').forEach((card) => {
            const name = card.querySelector('.name-item');
            const text = (name ? name.textContent : '').toLowerCase();
            card.style.display = text.includes(filter) ? '' : 'none';
        });

        clearResults(ctx);
        setListingVisible(ctx, true);
        updateSedeDropdowns();
    };

    const metaFor = (result) => {
        const meta = [];

        if (result.type === 'good') {
            meta.push('Inventario: ' + escapeHtml(result.inventory_name));
            meta.push('Grupo: ' + escapeHtml(result.group_name));
        } else if (result.type === 'inventory') {
            meta.push('Grupo: ' + escapeHtml(result.group_name));
        } else if (result.type === 'group') {
            const count = Number(result.inventories_count ?? 0);
            meta.push(count + ' inventario' + (count === 1 ? '' : 's'));
        }

        if (result.sede_name) {
            meta.push('Sede: ' + escapeHtml(result.sede_name));
        }

        if (result.asset_type) {
            meta.push('Tipo: ' + escapeHtml(result.asset_type));
        }

        return meta;
    };

    const resultCard = (result) => {
        const type = result.type || 'inventory';
        const icons = {
            group: 'fa-layer-group',
            inventory: 'fa-folder',
            good: 'fa-box',
        };
        const labels = {
            group: 'Grupo',
            inventory: 'Inventario',
            good: 'Bien',
        };
        const meta = metaFor(result)
            .map((item) => `<span class="stat-item"><i class="fas fa-circle"></i>${item}</span>`)
            .join('');

        return `
            <div class="card card-item">
                <div class="card-left">
                    <i class="fas ${icons[type] || 'fa-search'} icon-folder"></i>
                </div>
                <div class="card-center">
                    <div class="title name-item">${escapeHtml(result.title)}</div>
                    <div class="stats">
                        <span class="stat-item"><i class="fas fa-filter"></i>${labels[type] || 'Resultado'}</span>
                        ${meta}
                    </div>
                </div>
                <div class="card-right">
                    <button
                        type="button"
                        class="btn-open"
                        data-group-search-go-url="${escapeHtml(result.url)}"
                        data-group-search-result-type="${escapeHtml(type)}"
                        data-group-search-update-history="${result.update_history === false ? 'false' : 'true'}"
                    >
                        <i class="fas fa-arrow-right"></i> Ir
                    </button>
                </div>
            </div>
        `;
    };

    const showResults = (ctx, results) => {
        ctx.results.classList.remove('hidden');

        if (!results.length) {
            showMessage(
                ctx,
                ctx.mode.value === 'goods'
                    ? 'No se encontraron bienes.'
                    : 'No se encontraron inventarios.'
            );
            return;
        }

        ctx.results.innerHTML = `<div class="card-grid">${results.map(resultCard).join('')}</div>`;
    };

    const runRemoteSearch = () => {
        const ctx = elements();
        if (!ctx) {
            return;
        }

        const mode = ctx.mode.value;
        const term = ctx.input.value.trim();

        if (mode === 'groups') {
            applyLocalGroupSearch(ctx);
            return;
        }

        if (request) {
            request.abort();
            request = null;
        }

        if (term === '') {
            clearResults(ctx);
            document.querySelectorAll('[data-group-card]').forEach((card) => {
                card.style.display = '';
            });
            setListingVisible(ctx, true);
            updateSedeDropdowns();
            return;
        }

        setListingVisible(ctx, false);
        showMessage(ctx, 'Buscando...');

        const controller = new AbortController();
        request = controller;
        const params = new URLSearchParams({ type: mode, q: term });

        if (ctx.root.dataset.portalCatalog === '1') {
            params.set('portal', '1');
        }

        fetch('/api/groups/search?' + params.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Error al buscar');
                }

                return response.json();
            })
            .then((data) => showResults(ctx, Array.isArray(data.results) ? data.results : []))
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    console.error(error);
                    showMessage(ctx, 'No se pudo completar la busqueda.');
                }
            })
            .finally(() => {
                if (request === controller) {
                    request = null;
                }
            });
    };

    const sync = () => {
        const ctx = elements();
        if (!ctx) {
            return;
        }

        const placeholders = {
            groups: 'Buscar grupo...',
            inventories: 'Buscar inventario...',
            goods: 'Buscar bien...',
        };

        ctx.input.placeholder = placeholders[ctx.mode.value] || placeholders.groups;

        if (ctx.mode.value === 'groups') {
            applyLocalGroupSearch(ctx);
            return;
        }

        window.clearTimeout(timer);
        timer = window.setTimeout(runRemoteSearch, 220);
    };

    const boot = () => {
        const ctx = elements();
        if (!ctx || ctx.input.dataset.groupSearchCompatibilityBooted === '1') {
            return;
        }

        ctx.input.dataset.groupSearchCompatibilityBooted = '1';
        sync();
    };

    document.addEventListener('input', (event) => {
        if (event.target?.id !== 'searchGroup') {
            return;
        }

        event.stopImmediatePropagation();
        sync();
    }, true);

    document.addEventListener('change', (event) => {
        if (event.target?.id !== 'groupSearchMode') {
            return;
        }

        event.stopImmediatePropagation();
        sync();
    }, true);

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-group-search-go-url]');
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const initializer = button.dataset.groupSearchResultType === 'good'
            ? 'initGoodsInventoryFunctions'
            : 'initInventoryFunctions';

        if (typeof loadContent === 'function') {
            loadContent(button.dataset.groupSearchGoUrl, {
                updateHistory: button.dataset.groupSearchUpdateHistory !== 'false',
                onSuccess: () => {
                    if (typeof window[initializer] === 'function') {
                        window[initializer]();
                    }
                },
            });
            return;
        }

        window.location.assign(button.dataset.groupSearchGoUrl);
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    const mainContent = document.getElementById('main-content');
    if (mainContent && typeof MutationObserver === 'function') {
        new MutationObserver(boot).observe(mainContent, {
            childList: true,
            subtree: true,
        });
    }

    window.groupSearchCompatibilityFallback = { boot, sync };
})();
