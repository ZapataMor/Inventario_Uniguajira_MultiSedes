// Modal con los bienes que no se pudieron cargar en una carga masiva desde Excel.
// Muestra las filas rechazadas en una tabla editable igual a la previsualizacion,
// marca en rojo el dato que impidio la carga y en ambar los seriales duplicados
// (agrupados para verlos juntos), y permite reenviarlas o descargarlas en Excel.
//
// Cada modulo aporta lo mismo que ya usa en su previsualizacion: columnas,
// prepareRow, mapRow y la funcion que envia las filas al backend.

(function () {
    const IDS = {
        table: 'excelFailedTable',
        body: 'excelFailedBody',
    };

    function escapeHtml(value) {
        return ExcelUI.escapeHtml(value);
    }

    /**
     * Ordena las filas fallidas dejando juntas las que comparten serial.
     *
     * @param {object[]} sentRows  filas tal como se enviaron
     * @param {object[]} failures  `failed_rows` del backend
     * @returns {Array<{row: object, failure: object}>}
     */
    function orderFailedRows(sentRows, failures) {
        const byGroup = new Map();
        failures.forEach((failure) => {
            if (!failure.group) return;
            if (!byGroup.has(failure.group)) byGroup.set(failure.group, []);
            byGroup.get(failure.group).push(failure);
        });

        const emittedGroups = new Set();
        const ordered = [];

        [...failures]
            .sort((a, b) => a.index - b.index)
            .forEach((failure) => {
                if (!failure.group) {
                    ordered.push(failure);
                    return;
                }

                if (emittedGroups.has(failure.group)) return;
                emittedGroups.add(failure.group);
                ordered.push(...byGroup.get(failure.group).sort((a, b) => a.index - b.index));
            });

        return ordered
            .filter((failure) => sentRows[failure.index])
            .map((failure) => ({ row: sentRows[failure.index], failure }));
    }

    function buildDialog(config) {
        const overlay = document.createElement('div');
        overlay.className = 'excel-failed-overlay';

        const headers = ['Motivo', ...config.headers]
            .map((label) => `<th class="excel-preview-table-th">${escapeHtml(label)}</th>`)
            .join('');

        overlay.innerHTML = `
            <div class="excel-failed-dialog" role="dialog" aria-modal="true" aria-labelledby="excelFailedTitle">
                <div class="excel-failed-top">
                    <h3 id="excelFailedTitle" class="excel-failed-title">Bienes que no se pudieron cargar</h3>
                    <p class="excel-failed-summary" data-failed-summary></p>
                    <ul class="excel-failed-legend">
                        <li><span class="excel-failed-swatch excel-failed-swatch-error"></span>Dato que impidio la carga</li>
                        <li><span class="excel-failed-swatch excel-failed-swatch-duplicate"></span>Serial duplicado: las filas en conflicto aparecen juntas</li>
                    </ul>
                </div>
                <div class="excel-failed-body">
                    <div class="excel-failed-table-scroll">
                        <table id="${IDS.table}" class="excel-preview-table excel-failed-table">
                            <thead class="excel-preview-table-head"><tr>${headers}</tr></thead>
                            <tbody id="${IDS.body}" class="excel-preview-table-body"></tbody>
                        </table>
                    </div>
                </div>
                <div class="excel-failed-actions">
                    <button type="button" class="excel-failed-btn excel-failed-btn-download" data-failed-action="download">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <span class="excel-failed-actions-spacer"></span>
                    <button type="button" class="excel-failed-btn excel-failed-btn-close" data-failed-action="close">Cerrar</button>
                    <button type="button" class="excel-failed-btn excel-failed-btn-submit" data-failed-action="submit">
                        <i class="fas fa-paper-plane"></i> Volver a enviar
                    </button>
                </div>
            </div>`;

        return overlay;
    }

    function summaryText(created, pending) {
        const loaded = created > 0
            ? `Se cargaron ${created} bien(es) en total. `
            : '';

        return `${loaded}${pending} bien(es) no se cargaron. Corrige los datos marcados y vuelve a enviarlos, `
            + 'o descargalos en Excel para corregirlos despues.';
    }

    function referenceRowHtml(existing, columnCount) {
        return `
            <td colspan="${columnCount}">
                <span class="excel-failed-ref-label">Ya registrado</span>
                Serial <b>${escapeHtml(existing.serial)}</b>
                &middot; Bien <b>${escapeHtml(existing.bien)}</b>
                &middot; Inventario <b>${escapeHtml(existing.inventario)}</b>
            </td>`;
    }

    /**
     * @param {object} config
     * @param {string[]} config.headers        etiquetas de columnas (sin "Motivo")
     * @param {object[]} config.columns        columnas de ExcelUI.createPreviewManager
     * @param {Function} config.prepareRow
     * @param {Function} config.mapRow         (row, tr) => fila para enviar | null
     * @param {Function} config.submit         async (rows) => respuesta JSON del backend
     * @param {Array<{header: string, key: string}>} config.exportColumns
     * @param {object[]} config.sentRows       filas enviadas en el intento anterior
     * @param {object[]} config.failures       `failed_rows` de ese intento
     * @param {number}   [config.created]      bienes ya cargados en ese intento
     * @param {Function} [config.onRender]     (tbody, rows) despues de pintar las filas
     * @param {Function} [config.onClose]      ({created, pending}) al cerrar el modal
     */
    function open(config) {
        document.querySelector('.excel-failed-overlay')?.remove();

        const overlay = buildDialog(config);
        document.body.appendChild(overlay);

        const summary = overlay.querySelector('[data-failed-summary]');
        const submitButton = overlay.querySelector('[data-failed-action="submit"]');
        const failureByTr = new WeakMap();
        const columnCount = config.columns.length + 1;
        let totalCreated = Number(config.created) || 0;
        let busy = false;

        const columns = [
            {
                field: null,
                render: ({ row }) => `<span class="excel-failed-reason">${escapeHtml(row.__failure.message)}</span>`,
            },
            ...config.columns,
        ];

        const preview = ExcelUI.createPreviewManager({
            tableId: IDS.table,
            bodyId: IDS.body,
            prepareRow: config.prepareRow,
            columns,
        });
        const tbody = preview.elements.tbody;

        function pendingRows() {
            return tbody.querySelectorAll('tr:not(.excel-failed-ref-row)').length;
        }

        function refreshSummary() {
            const pending = pendingRows();
            summary.textContent = summaryText(totalCreated, pending);
            submitButton.disabled = pending === 0;
        }

        function render(sentRows, failures) {
            const items = orderFailedRows(sentRows, failures);
            preview.renderRows(items.map(({ row, failure }) => ({ ...row, __failure: failure })));

            const trs = Array.from(tbody.querySelectorAll('tr'));
            const groupSizes = new Map();
            items.forEach(({ failure }) => {
                if (failure.group) groupSizes.set(failure.group, (groupSizes.get(failure.group) || 0) + 1);
            });

            items.forEach(({ failure }, index) => {
                const tr = trs[index];
                const isDuplicate = failure.type === 'duplicate';
                failureByTr.set(tr, failure);

                tr.classList.add(isDuplicate ? 'excel-failed-row-duplicate' : 'excel-failed-row-error');

                if (isDuplicate) {
                    const previous = items[index - 1]?.failure;
                    if (previous?.group !== failure.group) tr.classList.add('excel-failed-group-start');
                }

                (failure.fields || []).forEach((field) => {
                    const cell = tr.querySelector(`[data-field="${field}"]`)?.closest('td');
                    cell?.classList.add(isDuplicate ? 'excel-failed-cell-duplicate' : 'excel-failed-cell-error');
                });

                if (failure.existing) {
                    const reference = document.createElement('tr');
                    reference.className = 'excel-failed-ref-row';
                    reference.innerHTML = referenceRowHtml(failure.existing, columnCount);
                    tr.after(reference);
                }
            });

            if (typeof config.onRender === 'function') {
                config.onRender(tbody, items.map(({ row }) => row));
            }

            refreshSummary();
        }

        function readEditedRows() {
            return preview.readRows((row, tr) => {
                if (tr.classList.contains('excel-failed-ref-row')) return null;
                const data = config.mapRow(row, tr);
                return data ? { data, failure: failureByTr.get(tr) } : null;
            });
        }

        function downloadExcel() {
            const items = readEditedRows();
            if (!items.length) return;

            const headers = [...config.exportColumns.map((column) => column.header), 'Motivo'];
            const data = items.map(({ data: row, failure }) => [
                ...config.exportColumns.map((column) => row[column.key] ?? ''),
                failure?.message ?? '',
            ]);

            const worksheet = XLSX.utils.aoa_to_sheet([headers, ...data]);
            worksheet['!cols'] = headers.map((header) => ({ wch: header === 'Motivo' ? 60 : 20 }));

            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'No cargados');
            XLSX.writeFile(workbook, `Bienes_no_cargados_${new Date().toISOString().slice(0, 10)}.xlsx`);
        }

        function close() {
            document.removeEventListener('keydown', onKeydown);
            overlay.remove();

            if (typeof config.onClose === 'function') {
                config.onClose({ created: totalCreated, pending: pendingRows() });
            }
        }

        async function confirmClose() {
            const pending = pendingRows();
            if (!pending) {
                close();
                return;
            }

            const message = `Quedan ${pending} bien(es) sin cargar. Si cierras, se perderan los cambios de este modal; `
                + 'puedes descargarlos en Excel antes de cerrar.';

            const confirmed = window.Swal
                ? (await Swal.fire({
                    icon: 'warning',
                    title: 'Cerrar sin cargar',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: 'Cerrar de todos modos',
                    cancelButtonText: 'Volver',
                    confirmButtonColor: '#e11d48',
                })).isConfirmed
                : window.confirm(message);

            if (confirmed) close();
        }

        async function resubmit() {
            if (busy) return;

            if (!(await AssetNameReview.confirm({ tbody, field: 'bien' }))) return;

            const rows = readEditedRows().map(({ data }) => data);
            if (!rows.length) return;

            busy = true;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            try {
                const data = await config.submit(rows);
                const failures = data.failed_rows || [];
                totalCreated += Number(data.created) || 0;
                showToast(data);

                if (failures.length) {
                    render(rows, failures);
                    return;
                }

                // Sin failed_rows y sin exito es un error general (p. ej. error interno): se conservan las filas.
                if (data.success) {
                    tbody.innerHTML = '';
                    close();
                }
            } catch (error) {
                console.error(error);
                showToast({ success: false, message: 'Error de conexion.' });
            } finally {
                busy = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Volver a enviar';
                if (overlay.isConnected) refreshSummary();
            }
        }

        const onKeydown = (event) => {
            if (event.key === 'Escape' && !document.querySelector('.name-review-overlay')) confirmClose();
        };

        // Al quitar una fila con referencia de "ya registrado", la referencia se va con ella.
        tbody.addEventListener('click', (event) => {
            if (!event.target.closest('[data-excel-remove-row="true"]')) return;
            const reference = event.target.closest('tr')?.nextElementSibling;
            if (reference?.classList.contains('excel-failed-ref-row')) reference.remove();
            setTimeout(refreshSummary);
        }, true);

        // Una celda corregida deja de verse como error.
        const markEdited = (event) => {
            const cell = event.target.closest('td');
            if (!cell?.matches('.excel-failed-cell-error, .excel-failed-cell-duplicate')) return;
            cell.classList.remove('excel-failed-cell-error', 'excel-failed-cell-duplicate');
            cell.classList.add('excel-failed-cell-edited');
        };
        tbody.addEventListener('input', markEdited);
        tbody.addEventListener('change', markEdited);

        overlay.addEventListener('click', (event) => {
            const action = event.target.closest('[data-failed-action]')?.dataset.failedAction;
            if (action === 'download') downloadExcel();
            if (action === 'close') confirmClose();
            if (action === 'submit') resubmit();
        });

        document.addEventListener('keydown', onKeydown);
        render(config.sentRows, config.failures);
    }

    window.ExcelFailedRows = { open, orderFailedRows };
})();
