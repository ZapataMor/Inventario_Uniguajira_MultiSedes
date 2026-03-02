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
    currentFolderId = idFolder;

    const divFolders = document.getElementById('folders');
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
    const reportContentItem = document.getElementById('report-content-item');

    if (reportContent) reportContent.classList.add('hidden');
    if (folders) folders.classList.remove('hidden');
    if (reportContentItem) reportContentItem.innerHTML = '';
}

function updateAllFolderIdFields(folderId) {
    const folderIdFields = [
        'folderIdInventario',
        'folderIdGrupo',
        'folderIdTodosLosInventarios',
        'folderIdDeBienes',
        'folderIdDeEquipos'
    ];

    folderIdFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) field.value = folderId;
    });
}
