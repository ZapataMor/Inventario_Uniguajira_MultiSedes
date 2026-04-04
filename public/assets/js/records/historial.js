function initHistorialFunctions() {
    activateRecordSearch();
    initPortalRecordDropdowns();
}

function activateRecordSearch() {
    const searchInput = document.getElementById('searchRecordInput');

    if (!searchInput) {
        return;
    }

    const applySearch = () => {
        const filter = searchInput.value.toLowerCase().trim();
        const rows = document.querySelectorAll('[data-record-row]');

        rows.forEach((row) => {
            const searchableText = (row.textContent || '').toLowerCase();
            row.style.display = searchableText.includes(filter) ? '' : 'none';
        });
    };

    ['input', 'keyup', 'search'].forEach((eventName) => {
        searchInput.addEventListener(eventName, applySearch);
    });

    applySearch();
}

function initPortalRecordDropdowns() {
    const dropdowns = document.querySelectorAll('[data-record-sede-dropdown]');
    const searchInput = document.getElementById('searchRecordInput');

    if (!dropdowns.length || !searchInput) {
        return;
    }

    const controllers = Array.from(dropdowns).map((dropdown) => ({
        dropdown,
        controller: getRecordSedeDropdownController(dropdown, '.inventory-sede-body'),
    }));

    const updateDropdownState = () => {
        const hasFilter = searchInput.value.trim().length > 0;

        controllers.forEach(({ dropdown, controller }) => {
            const rows = dropdown.querySelectorAll('[data-record-row]');
            const visibleRows = Array.from(rows).filter((row) => row.style.display !== 'none');
            const visibleCountBadge = dropdown.querySelector('[data-visible-count]');
            const emptyByFilterMessage = dropdown.querySelector('[data-sede-empty]');

            if (visibleCountBadge) {
                visibleCountBadge.textContent = String(visibleRows.length);
            }

            if (emptyByFilterMessage) {
                emptyByFilterMessage.classList.toggle('hidden', visibleRows.length > 0);
            }

            if (hasFilter) {
                controller.setOpen(visibleRows.length > 0, true);
            } else {
                controller.setOpen(false, true);
            }
        });
    };

    ['input', 'keyup', 'search'].forEach((eventName) => {
        searchInput.addEventListener(eventName, updateDropdownState);
    });

    updateDropdownState();
}

function getRecordSedeDropdownController(dropdown, bodySelector) {
    if (typeof createSedeDropdownController === 'function') {
        return createSedeDropdownController(dropdown, bodySelector);
    }

    return {
        setOpen: (shouldOpen) => {
            dropdown.open = shouldOpen;
        }
    };
}
