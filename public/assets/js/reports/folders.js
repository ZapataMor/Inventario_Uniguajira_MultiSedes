let currentFolderId = null;

function bindFolderAjaxFormOnce(formSelector, options) {
    const form = document.querySelector(formSelector);
    if (!form || form.dataset.ajaxInit === '1') return;

    inicializarFormularioAjax(formSelector, options);
    form.dataset.ajaxInit = '1';
}

function initFoldersFunctions() {
    bindFolderAjaxFormOnce('#formCrearCarpeta', {
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/reports', { onSuccess: () => initReportsModule() });
        }
    });

    bindFolderAjaxFormOnce('#formRenombrarCarpeta', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/reports', { onSuccess: () => initReportsModule() });
        }
    });

    iniciarBusqueda('searchFolder');
    initPortalReportDropdowns();
}

function initFolderFunctions() {
    initFoldersFunctions();
}

function btnRenombrarCarpeta() {
    if (!selectedItem || selectedItem.type !== 'folder') return;

    document.getElementById('carpetaRenombrarId').value = selectedItem.id;
    document.getElementById('carpetaRenombrarNombre').value = selectedItem.name;
    mostrarModal('#modalRenombrarCarpeta');
}

function btnEliminarCarpeta() {
    if (!selectedItem || selectedItem.type !== 'folder') return;

    eliminarRegistro({
        url: `/api/folders/delete/${selectedItem.id}`,
        onSuccess: (response) => {
            showToast(response);
            loadContent('/reports', { onSuccess: () => initReportsModule() });
        }
    });
}

function abrirCarpeta(idFolder, scrollUpRequired = true) {
    // Evita inconsistencia visual: al abrir una carpeta se limpia la seleccion activa.
    if (typeof deselectItem === 'function') {
        deselectItem();
    }

    currentFolderId = idFolder;

    const divFolders = document.getElementById('folders');
    const foldersTopbar = document.getElementById('folders-topbar');
    const divReports = document.getElementById('report-content');
    const divContent = document.getElementById('report-content-item');
    const folderTitle = document.getElementById('folder-name');

    if (!divFolders || !divReports || !divContent) return;

    const folderCard = document.querySelector(`[data-type="folder"][data-id="${idFolder}"]`);
    if (folderTitle) {
        folderTitle.textContent = folderCard?.dataset?.name || 'Reportes';
    }

    updateAllFolderIdFields(idFolder);
    divContent.innerHTML = '<p>Cargando reportes...</p>';
    if (foldersTopbar) foldersTopbar.classList.add('hidden');
    divFolders.classList.add('hidden');
    divReports.classList.remove('hidden');

    fetch(`/api/reports/getAll/${idFolder}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(res => res.text())
        .then(html => {
            divContent.innerHTML = html;
            iniciarBusqueda('searchReport');

            if (typeof initReportsFunctions === 'function') {
                initReportsFunctions();
            }

            if (scrollUpRequired) {
                window.scrollTo(0, 0);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            divContent.innerHTML = '<p>Error al cargar los reportes.</p>';
        });
}

function cerrarCarpeta() {
    currentFolderId = null;
    updateAllFolderIdFields('');

    const reportContent = document.getElementById('report-content');
    const folders = document.getElementById('folders');
    const foldersTopbar = document.getElementById('folders-topbar');
    const reportContentItem = document.getElementById('report-content-item');

    if (reportContent) reportContent.classList.add('hidden');
    if (folders) folders.classList.remove('hidden');
    if (foldersTopbar) foldersTopbar.classList.remove('hidden');
    if (reportContentItem) reportContentItem.innerHTML = '';
}

function updateAllFolderIdFields(folderId) {
    const folderIdFields = [
        'folderIdInventario',
        'folderIdGrupo',
        'folderIdTodosLosInventarios',
        'folderIdDeBienes',
        'folderIdDeEquipos',
        'folderIdDadosDeBaja',
        'folderIdHistorial'
    ];

    folderIdFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) field.value = folderId;
    });
}

function initPortalReportDropdowns() {
    const dropdowns = document.querySelectorAll('[data-report-sede-dropdown]');
    const searchInput = document.getElementById('searchFolder');

    if (!dropdowns.length || !searchInput) {
        return;
    }

    const controllers = Array.from(dropdowns).map((dropdown) => ({
        dropdown,
        controller: getReportSedeDropdownController(dropdown, '.inventory-sede-body'),
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

function getReportSedeDropdownController(dropdown, bodySelector) {
    if (typeof createSedeDropdownController === 'function') {
        return createSedeDropdownController(dropdown, bodySelector);
    }

    return {
        setOpen: (shouldOpen) => {
            dropdown.open = shouldOpen;
        }
    };
}
