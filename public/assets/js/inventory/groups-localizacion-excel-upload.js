// Carga masiva de bienes distribuidos por localización (nombre de inventario).

const LOC_EXCEL_CONFIG = {
    validTypes: ['.xlsx', '.xls'],
    requiredHeaders: ['bien', 'tipo', 'localizacion'],
};

const LOC_EXCEL_STATE = {
    preview: null,
};

// ── Helpers para reinyectar celdas cuando cambia el tipo ──────────────────────
const LOC_CELL = {
    editable(field, value = '') {
        return `<div class="excel-preview-edit-cell block min-w-[72px] w-full rounded-xl border border-transparent bg-white px-3 py-2 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-emerald-500 focus:bg-emerald-50/70 focus:ring-4 focus:ring-emerald-100" contenteditable="plaintext-only" data-field="${field}">${value}</div>`;
    },
    placeholder() {
        return `<span class="excel-preview-disabled inline-flex min-w-[72px] items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium italic text-slate-400">-</span>`;
    },
};

// Índices de columna dentro de cada <tr> (0-based)
const COL_INDEX = { serial: 2, cantidad: 3 };

const LOC_EXCEL_COLUMNS = [
    { field: 'bien', type: 'text' },
    {
        field: 'tipo',
        type: 'select',
        value: ({ values }) => values.tipo,
        options: [
            { value: 'Serial', label: 'Serial' },
            { value: 'Cantidad', label: 'Cantidad' },
        ],
    },
    {
        field: 'serial',
        render: ({ values, helpers }) => values.esSerial
            ? helpers.editable('serial', values.serial)
            : helpers.placeholder('-'),
    },
    {
        field: 'cantidad',
        render: ({ values, helpers }) => values.esSerial
            ? helpers.placeholder('-')
            : helpers.editable('cantidad', values.cantidad),
    },
    { field: 'marca', type: 'text' },
    { field: 'modelo', type: 'text' },
    {
        field: 'estado',
        type: 'select',
        value: ({ values }) => values.estado,
        options: [
            { value: 'activo', label: 'activo' },
            { value: 'inactivo', label: 'inactivo' },
        ],
    },
    { field: 'localizacion', type: 'text' },
    { type: 'remove', align: 'center', padding: '4px 10px', title: 'Eliminar fila' },
];

function locPrepareRow(row) {
    const tipo = String(row.tipo ?? 'Serial').trim().toLowerCase() === 'cantidad' ? 'Cantidad' : 'Serial';

    return {
        bien: String(row.bien ?? '').trim(),
        tipo,
        esSerial: tipo === 'Serial',
        serial: String(row.serial ?? '').trim(),
        cantidad: String(row.cantidad ?? '1').trim() || '1',
        marca: String(row.marca ?? '').trim(),
        modelo: String(row.modelo ?? '').trim(),
        estado: String(row.estado ?? '').trim() === 'inactivo' ? 'inactivo' : 'activo',
        localizacion: String(row.localizacion ?? '').trim(),
    };
}

// ── Escucha cambios en el select "tipo" y actualiza las celdas serial/cantidad ─
function bindTipoSelect(tbody) {
    tbody.addEventListener('change', (event) => {
        const select = event.target;
        if (!select.matches('[data-field="tipo"]')) return;

        const tr = select.closest('tr');
        if (!tr) return;

        const tds = tr.querySelectorAll('td');
        const serialTd = tds[COL_INDEX.serial];
        const cantidadTd = tds[COL_INDEX.cantidad];
        if (!serialTd || !cantidadTd) return;

        const esSerial = select.value === 'Serial';

        if (esSerial) {
            serialTd.innerHTML = LOC_CELL.editable('serial', '');
            cantidadTd.innerHTML = LOC_CELL.placeholder();
        } else {
            serialTd.innerHTML = LOC_CELL.placeholder();
            cantidadTd.innerHTML = LOC_CELL.editable('cantidad', '1');
        }
    });
}

function abrirExcelLocalizacion() {
    loadContent(
        '/groups/localizacion-excel-upload',
        { onSuccess: () => initLocalizacionExcelUploadView() }
    );
}

function initLocalizacionExcelUploadView() {
    const tbody = document.getElementById('locPreviewBody');

    LOC_EXCEL_STATE.preview = ExcelUI.createPreviewManager({
        tableId: 'locPreviewTable',
        bodyId: 'locPreviewBody',
        clearButtonId: 'btnLimpiarExcelLocalizacion',
        submitButtonId: 'btnEnviarExcelLocalizacion',
        errorListId: 'locErrorList',
        errorItemsId: 'locErrorItems',
        prepareRow: locPrepareRow,
        columns: LOC_EXCEL_COLUMNS,
        onClear: locLimpiarUI,
        onSubmit: locEnviarDatos,
    });

    if (tbody) bindTipoSelect(tbody);

    ExcelUI.initUploadArea({
        areaId: 'loc-excel-upload-area',
        inputId: 'locExcelFileInput',
        onFileSelected: (files) => {
            locHandleFile(files[0]);
        },
    });

    locLimpiarUI();
}

async function locHandleFile(file) {
    if (!file) return;

    if (!ExcelUI.hasValidExtension(file.name, LOC_EXCEL_CONFIG.validTypes)) {
        showToast({ success: false, message: 'Formato inválido. Use .xlsx o .xls' });
        return;
    }

    try {
        const jsonData = await ExcelUI.readExcelFile(file);
        const rows = locParsearFilas(jsonData);

        if (!rows.length) {
            showToast({ success: false, message: 'No se encontraron datos válidos.' });
            return;
        }

        LOC_EXCEL_STATE.preview?.renderRows(rows);
        showToast({ success: true, message: `${rows.length} fila(s) lista(s) para enviar.` });
    } catch (error) {
        console.error(error);
        showToast({ success: false, message: 'Error al leer el archivo Excel.' });
    }
}

function locParsearFilas(jsonData) {
    if (!jsonData.length) return [];

    const headers = ExcelUI.normalizeHeaders(jsonData[0]);
    const hasRequired = LOC_EXCEL_CONFIG.requiredHeaders.every((h) => headers.includes(h));

    if (!hasRequired) {
        showToast({
            success: false,
            message: `El archivo debe tener las columnas: ${LOC_EXCEL_CONFIG.requiredHeaders.join(', ')}`,
        });
        return [];
    }

    const idx = {
        bien:         ExcelUI.findColumnIndex(headers, ['bien']),
        tipo:         ExcelUI.findColumnIndex(headers, ['tipo']),
        serial:       ExcelUI.findColumnIndex(headers, ['serial']),
        cantidad:     ExcelUI.findColumnIndex(headers, ['cantidad']),
        marca:        ExcelUI.findColumnIndex(headers, ['marca']),
        modelo:       ExcelUI.findColumnIndex(headers, ['modelo']),
        descripcion:  ExcelUI.findColumnIndex(headers, ['descripcion']),
        estado:       ExcelUI.findColumnIndex(headers, ['estado']),
        color:        ExcelUI.findColumnIndex(headers, ['color']),
        condiciones:  ExcelUI.findColumnIndex(headers, ['condiciones']),
        fecha:        ExcelUI.findColumnIndex(headers, ['fecha ingreso', 'fecha_ingreso']),
        localizacion: ExcelUI.findColumnIndex(headers, ['localizacion', 'localización']),
    };

    const rows = [];

    jsonData.slice(1).forEach((row, index) => {
        const bien = String(row[idx.bien] ?? '').trim();

        // Ignorar filas vacías, marcadas como n/a o notas de la plantilla (inician con *)
        if (!bien || bien.toLowerCase() === 'n/a' || bien.startsWith('*')) return;

        rows.push({
            bien,
            tipo:         idx.tipo >= 0         ? String(row[idx.tipo] ?? 'Serial').trim()   : 'Serial',
            serial:       idx.serial >= 0        ? String(row[idx.serial] ?? '').trim()       : '',
            cantidad:     idx.cantidad >= 0      ? String(row[idx.cantidad] ?? '1').trim()    : '1',
            marca:        idx.marca >= 0         ? String(row[idx.marca] ?? '').trim()        : '',
            modelo:       idx.modelo >= 0        ? String(row[idx.modelo] ?? '').trim()       : '',
            descripcion:  idx.descripcion >= 0   ? String(row[idx.descripcion] ?? '').trim()  : '',
            estado:       idx.estado >= 0        ? String(row[idx.estado] ?? 'activo').trim() : 'activo',
            color:        idx.color >= 0         ? String(row[idx.color] ?? '').trim()        : '',
            condiciones:  idx.condiciones >= 0   ? String(row[idx.condiciones] ?? '').trim()  : '',
            fecha_ingreso:idx.fecha >= 0         ? String(row[idx.fecha] ?? '').trim()        : '',
            localizacion: idx.localizacion >= 0  ? String(row[idx.localizacion] ?? '').trim() : '',
            _rowNum: index + 2,
        });
    });

    return rows;
}

function locLeerFilasDeDOM() {
    return LOC_EXCEL_STATE.preview?.readRows((row) => {
        if (!row.bien) return null;

        const esSerial = row.tipo === 'Serial';

        return {
            bien:         row.bien,
            tipo:         row.tipo,
            serial:       esSerial ? (row.serial ?? '') : null,
            cantidad:     esSerial ? null : (row.cantidad || '1'),
            marca:        row.marca ?? '',
            modelo:       row.modelo ?? '',
            estado:       row.estado ?? 'activo',
            localizacion: row.localizacion ?? '',
        };
    }) || [];
}

async function locEnviarDatos() {
    const rows = locLeerFilasDeDOM();

    if (!rows.length) return;

    const preview = LOC_EXCEL_STATE.preview;
    const button = preview?.elements.submitButton;

    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    }

    preview?.clearErrors();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch('/api/goods-inventory/batchCreateByLocalizacion', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ rows }),
        });

        const data = await response.json();
        showToast(data);

        if (data.errors && data.errors.length) {
            preview?.showErrors(data.errors);
        }

        if (data.success) {
            loadContent('/groups', { onSuccess: () => initGroupFunctions() });
        }
    } catch (error) {
        console.error(error);
        showToast({ success: false, message: 'Error de conexión.' });
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = 'Enviar';
        }

        preview?.updateSubmitButton();
    }
}

function descargarPlantillaLocalizacion() {
    window.location.href = '/api/goods-inventory/download-localizacion-template';
}

function locLimpiarUI() {
    ExcelUI.resetFileInput('locExcelFileInput');
    LOC_EXCEL_STATE.preview?.clear();
}

window.abrirExcelLocalizacion = abrirExcelLocalizacion;
window.descargarPlantillaLocalizacion = descargarPlantillaLocalizacion;
window.initLocalizacionExcelUploadView = initLocalizacionExcelUploadView;
