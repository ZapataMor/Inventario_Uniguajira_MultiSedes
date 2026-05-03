const datosInventarios = {
    grupos: [],
    inventariosPorGrupo: {}
};

function bindAjaxFormOnce(formSelector, options) {
    const form = document.querySelector(formSelector);
    if (!form || form.dataset.ajaxInit === '1') return;

    inicializarFormularioAjax(formSelector, options);
    form.dataset.ajaxInit = '1';
}

function cargarGrupos() {
    if (datosInventarios.grupos.length > 0) {
        llenarSelectsGrupos(datosInventarios.grupos);
        return Promise.resolve(datosInventarios.grupos);
    }

    return fetch('/api/groups/getAll', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => response.json())
        .then(data => {
            datosInventarios.grupos = Array.isArray(data) ? data : [];
            llenarSelectsGrupos(datosInventarios.grupos);
            return datosInventarios.grupos;
        })
        .catch(error => {
            console.error('Error al cargar los grupos:', error);
            alert('Error al cargar los grupos. Intentalo de nuevo.');
            throw error;
        });
}

function llenarSelectsGrupos(grupos) {
    const selectGruposIds = ['grupoSeleccionado', 'grupoSeleccionadoOfGrupo'];

    selectGruposIds.forEach(selectId => {
        const selectGrupos = document.getElementById(selectId);
        if (!selectGrupos) return;

        const previous = selectGrupos.value;
        selectGrupos.innerHTML = '<option value="">Seleccione un grupo</option>';

        grupos.forEach(grupo => {
            const option = document.createElement('option');
            option.value = grupo.id;
            option.textContent = grupo.nombre || grupo.name || `Grupo ${grupo.id}`;
            selectGrupos.appendChild(option);
        });

        if (previous) {
            selectGrupos.value = previous;
        }
    });
}

function cargarInventariosPorGrupo(grupoId) {
    if (!grupoId) {
        actualizarSelectInventarios([]);
        return;
    }

    if (datosInventarios.inventariosPorGrupo[grupoId]) {
        actualizarSelectInventarios(datosInventarios.inventariosPorGrupo[grupoId]);
        return;
    }

    fetch(`/api/inventories/getByGroupId/${grupoId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => response.json())
        .then(data => {
            const inventories = Array.isArray(data) ? data : [];
            datosInventarios.inventariosPorGrupo[grupoId] = inventories;
            actualizarSelectInventarios(inventories);
        })
        .catch(error => {
            console.error('Error al cargar inventarios:', error);
            alert('Error al cargar los inventarios. Intentalo de nuevo.');
        });
}

function actualizarSelectInventarios(inventarios) {
    const selectInventarios = document.getElementById('inventarioSeleccionado');
    if (!selectInventarios) return;

    if (inventarios.length > 0) {
        selectInventarios.disabled = false;
        selectInventarios.innerHTML = '<option value="">Seleccione un inventario</option>';

        inventarios.forEach(inventario => {
            const option = document.createElement('option');
            option.value = inventario.id;
            option.textContent = inventario.nombre || inventario.name || `Inventario ${inventario.id}`;
            selectInventarios.appendChild(option);
        });
        return;
    }

    selectInventarios.disabled = true;
    selectInventarios.innerHTML = '<option value="">No hay inventarios disponibles</option>';
}

function inicializarModalReporte(modalId) {
    cargarGrupos();

    if (modalId === '#modalCrearReporteInventario') {
        const selectInventarios = document.getElementById('inventarioSeleccionado');
        if (selectInventarios) {
            selectInventarios.disabled = true;
            selectInventarios.innerHTML = '<option value="">Primero seleccione un grupo</option>';
        }
    }
}

function refreshReportFolder() {
    if (typeof currentFolderId !== 'undefined' && currentFolderId) {
        abrirCarpeta(currentFolderId, false);
        return;
    }

    loadContent('/reports', { onSuccess: () => initReportsModule() });
}

function initFormsReporte() {
    bindAjaxFormOnce('#formReporteDeUnInventario', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });

    bindAjaxFormOnce('#formReporteDeUnGrupo', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });

    bindAjaxFormOnce('#formReporteDeTodosLosInventarios', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });

    bindAjaxFormOnce('#formReporteDeBienes', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });

    bindAjaxFormOnce('#formReporteDeEquipos', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });

    bindAjaxFormOnce('#formReporteDeDadosDeBaja', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });

    bindAjaxFormOnce('#formReporteDeHistorial', {
        resetOnSuccess: true,
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });
}

function initReportsFunctions() {
    bindAjaxFormOnce('#formRenombrarReporte', {
        closeModalOnSuccess: true,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });
}

function wireReportEvents() {
    const selectGrupo = document.getElementById('grupoSeleccionado');
    if (selectGrupo && selectGrupo.dataset.bound !== '1') {
        selectGrupo.addEventListener('change', function() {
            cargarInventariosPorGrupo(this.value);
        });
        selectGrupo.dataset.bound = '1';
    }
}

function initReportsModule() {
    if (typeof initFoldersFunctions === 'function') {
        initFoldersFunctions();
    }

    initFormsReporte();
    initReportsFunctions();
    wireReportEvents();
    iniciarBusqueda('searchFolder');
}

function mostrarModalReporte(modalId) {
    if (!currentFolderId) {
        alert('Debes abrir una carpeta para crear reportes.');
        return;
    }

    updateAllFolderIdFields(currentFolderId);
    inicializarModalReporte(modalId);
    mostrarModal(modalId);
}

function downloadReport(event, reportId, reportName, extension = 'pdf') {
    event.stopPropagation();

    const button = event.target.closest('button');
    if (!button) return;

    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Descargando...';
    button.disabled = true;

    const formData = new FormData();
    formData.append('report_id', reportId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

    fetch('/api/reports/download', {
        method: 'POST',
        body: formData
    })
        .then(async (response) => {
            if (!response.ok) {
                let message = 'No se pudo descargar el reporte.';
                try {
                    const errorJson = await response.json();
                    if (errorJson?.message) message = errorJson.message;
                } catch (_) {
                    // Ignorar parse error.
                }
                throw new Error(message);
            }

            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${reportName}.${extension}`;
            document.body.appendChild(a);
            a.click();

            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Error al descargar reporte:', error);
            alert(error.message || 'Error al descargar el reporte.');
        })
        .finally(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        });
}

function btnRenombrarReporte() {
    if (!selectedItem || selectedItem.type !== 'report') return;

    document.getElementById('reporteRenombrarId').value = selectedItem.id;
    document.getElementById('reporteRenombrarNombre').value = selectedItem.name;
    mostrarModal('#modalRenombrarReporte');
}

function btnEliminarReporte() {
    if (!selectedItem || selectedItem.type !== 'report') return;

    eliminarRegistro({
        url: `/api/reports/delete/${selectedItem.id}`,
        onSuccess: (response) => {
            showToast(response);
            refreshReportFolder();
        }
    });
}
