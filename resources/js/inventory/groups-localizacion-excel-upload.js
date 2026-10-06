// Carga masiva de bienes distribuidos por localizacion (nombre de inventario).

const LOC_EXCEL_CONFIG = {
    validTypes: ['.xlsx', '.xls'],
    requiredHeaders: ['bien', 'tipo', 'localizacion'],
};

const LOC_EXCEL_STATE = {
    preview: null,
};

const LOC_ESTADO_OPTIONS = [
    { value: 'activo', label: 'activo' },
    { value: 'inactivo', label: 'inactivo' },
];

// Campos que solo aplican a bienes tipo Serial (los de Cantidad solo suman unidades).
const LOC_SERIAL_FIELDS = ['serial', 'marca', 'modelo', 'estado', 'descripcion', 'color', 'condiciones', 'fecha_ingreso'];

const LOC_CACHE_FIELDS = ['cantidad', ...LOC_SERIAL_FIELDS];

const LOC_PREVIEW_HEADERS = [
    'Bien', 'Tipo', 'Serial', 'Cantidad', 'Marca', 'Modelo', 'Estado',
    'Descripcion', 'Color', 'Condiciones', 'Fecha ingreso', 'Localizacion', '',
];

// Mismo orden y encabezados que la plantilla, para poder volver a subir el archivo.
const LOC_EXPORT_COLUMNS = [
    { header: 'Bien*', key: 'bien' },
    { header: 'Tipo*', key: 'tipo' },
    { header: 'Serial', key: 'serial' },
    { header: 'Cantidad', key: 'cantidad' },
    { header: 'Marca', key: 'marca' },
    { header: 'Modelo', key: 'modelo' },
    { header: 'Descripcion', key: 'descripcion' },
    { header: 'Estado', key: 'estado' },
    { header: 'Color', key: 'color' },
    { header: 'Condiciones', key: 'condiciones' },
    { header: 'Fecha Ingreso', key: 'fecha_ingreso' },
    { header: 'Localizacion', key: 'localizacion' },
];

const LOC_CELL = {
    editable(field, value = '') {
        return `<div class="excel-preview-edit-cell block min-w-[72px] w-full rounded-xl border border-transparent bg-white px-3 py-2 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-emerald-500 focus:bg-emerald-50/70 focus:ring-4 focus:ring-emerald-100" contenteditable="plaintext-only" data-field="${field}">${ExcelUI.escapeHtml(value)}</div>`;
    },
    select(field, value, choices = []) {
        const options = choices.map((choice) => {
            const selected = String(choice.value) === String(value) ? 'selected' : '';
            return `<option value="${choice.value}" ${selected}>${choice.label}</option>`;
        }).join('');

        return `<select class="excel-preview-edit-select w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none transition hover:border-slate-300 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100" data-field="${field}">${options}</select>`;
    },
    // data-loc-slot ubica la celda aunque el campo no aplique, sin depender de su posicion.
    hiddenPlaceholder(field) {
        return `<span class="sr-only" data-loc-slot="${field}">No aplica para este tipo de bien</span>`;
    },
};

function locSerialCell(field, value) {
    return field === 'estado'
        ? LOC_CELL.select('estado', value || 'activo', LOC_ESTADO_OPTIONS)
        : LOC_CELL.editable(field, value ?? '');
}

function locSerialColumn(field) {
    return {
        field,
        render: ({ values }) => values.esSerial
            ? locSerialCell(field, values[field])
            : LOC_CELL.hiddenPlaceholder(field),
    };
}

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
    locSerialColumn('serial'),
    {
        field: 'cantidad',
        render: ({ values, helpers }) => values.esSerial
            ? LOC_CELL.hiddenPlaceholder('cantidad')
            : helpers.editable('cantidad', values.cantidad),
    },
    locSerialColumn('marca'),
    locSerialColumn('modelo'),
    locSerialColumn('estado'),
    locSerialColumn('descripcion'),
    locSerialColumn('color'),
    locSerialColumn('condiciones'),
    locSerialColumn('fecha_ingreso'),
    { field: 'localizacion', type: 'text' },
    { type: 'remove', align: 'center', padding: '4px 10px', title: 'Eliminar fila' },
];

function locNormalizeTipo(value) {
    return String(value ?? 'Serial').trim().toLowerCase() === 'cantidad' ? 'Cantidad' : 'Serial';
}

function locNormalizeEstado(value) {
    return String(value ?? '').trim().toLowerCase() === 'inactivo' ? 'inactivo' : 'activo';
}

function locCacheKey(field) {
    return `locCache${field.charAt(0).toUpperCase()}${field.slice(1)}`;
}

function locReadFieldValue(tr, field) {
    const element = tr?.querySelector(`[data-field="${field}"]`);
    if (!element) {
        return tr?.dataset[locCacheKey(field)] ?? '';
    }

    if (element.tagName === 'SELECT') {
        return element.value;
    }

    return ExcelUI.normalizeText(element.textContent);
}

function locWriteFieldCache(tr, field, value) {
    if (!tr) return;
    tr.dataset[locCacheKey(field)] = String(value ?? '');
}

function locCacheVisibleFields(tr) {
    LOC_CACHE_FIELDS.forEach((field) => {
        locWriteFieldCache(tr, field, locReadFieldValue(tr, field));
    });
}

function locGetFieldCell(tr, field) {
    return tr?.querySelector(`[data-field="${field}"], [data-loc-slot="${field}"]`)?.closest('td') ?? null;
}

function locRenderTypeFields(tr, tipo) {
    const esSerial = tipo === 'Serial';
    const cantidadTd = locGetFieldCell(tr, 'cantidad');

    if (cantidadTd) {
        cantidadTd.innerHTML = esSerial
            ? LOC_CELL.hiddenPlaceholder('cantidad')
            : LOC_CELL.editable('cantidad', tr.dataset[locCacheKey('cantidad')] || '1');
    }

    LOC_SERIAL_FIELDS.forEach((field) => {
        const td = locGetFieldCell(tr, field);
        if (!td) return;

        td.innerHTML = esSerial
            ? locSerialCell(field, tr.dataset[locCacheKey(field)] ?? '')
            : LOC_CELL.hiddenPlaceholder(field);
    });
}

function locSeedRowCaches(rows, tbody = LOC_EXCEL_STATE.preview?.elements.tbody) {
    const renderedRows = tbody?.querySelectorAll('tr:not(.excel-failed-ref-row)') ?? [];

    rows.forEach((row, index) => {
        const tr = renderedRows[index];
        if (!tr) return;

        const values = locPrepareRow(row);
        LOC_CACHE_FIELDS.forEach((field) => locWriteFieldCache(tr, field, values[field]));
    });
}

function locPrepareRow(row) {
    const tipo = locNormalizeTipo(row.tipo);

    return {
        bien: String(row.bien ?? '').trim(),
        tipo,
        esSerial: tipo === 'Serial',
        serial: String(row.serial ?? '').trim(),
        cantidad: String(row.cantidad ?? '1').trim() || '1',
        marca: String(row.marca ?? '').trim(),
        modelo: String(row.modelo ?? '').trim(),
        estado: locNormalizeEstado(row.estado),
        descripcion: String(row.descripcion ?? '').trim(),
        color: String(row.color ?? '').trim(),
        condiciones: String(row.condiciones ?? '').trim(),
        fecha_ingreso: ExcelUI.normalizeDate(row.fecha_ingreso),
        localizacion: String(row.localizacion ?? '').trim(),
    };
}

function bindTipoSelect(tbody) {
    if (!tbody || tbody.dataset.locTipoBound === '1') return;

    tbody.addEventListener('input', (event) => {
        const field = event.target?.dataset?.field;
        if (!LOC_CACHE_FIELDS.includes(field)) return;

        const tr = event.target.closest('tr');
        locWriteFieldCache(tr, field, locReadFieldValue(tr, field));
    });

    tbody.addEventListener('change', (event) => {
        const field = event.target?.dataset?.field;
        const tr = event.target.closest('tr');

        if (LOC_CACHE_FIELDS.includes(field)) {
            locWriteFieldCache(tr, field, locReadFieldValue(tr, field));
        }

        if (field !== 'tipo' || !tr) return;

        locCacheVisibleFields(tr);
        locRenderTypeFields(tr, event.target.value);
    });

    tbody.dataset.locTipoBound = '1';
}

function abrirExcelLocalizacion() {
    loadContent(
        '/groups/localizacion-excel-upload',
        { onSuccess: () => initLocalizacionExcelUploadView() },
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

    bindTipoSelect(tbody);

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
        showToast({ success: false, message: 'Formato invalido. Use .xlsx o .xls' });
        return;
    }

    try {
        const jsonData = await ExcelUI.readExcelFile(file);
        const rows = locParsearFilas(jsonData);

        if (!rows.length) {
            showToast({ success: false, message: 'No se encontraron datos validos.' });
            return;
        }

        LOC_EXCEL_STATE.preview?.renderRows(rows);
        locSeedRowCaches(rows);
        showToast({ success: true, message: `${rows.length} fila(s) lista(s) para enviar.` });
    } catch (error) {
        console.error(error);
        showToast({ success: false, message: 'Error al leer el archivo Excel.' });
    }
}

function locParsearFilas(jsonData) {
    if (!jsonData.length) return [];

    const headers = ExcelUI.normalizeHeaders(jsonData[0]);
    const hasRequired = LOC_EXCEL_CONFIG.requiredHeaders.every((header) => headers.includes(header));

    if (!hasRequired) {
        showToast({
            success: false,
            message: `El archivo debe tener las columnas: ${LOC_EXCEL_CONFIG.requiredHeaders.join(', ')}`,
        });
        return [];
    }

    const idx = {
        bien: ExcelUI.findColumnIndex(headers, ['bien']),
        tipo: ExcelUI.findColumnIndex(headers, ['tipo']),
        serial: ExcelUI.findColumnIndex(headers, ['serial']),
        cantidad: ExcelUI.findColumnIndex(headers, ['cantidad']),
        marca: ExcelUI.findColumnIndex(headers, ['marca']),
        modelo: ExcelUI.findColumnIndex(headers, ['modelo']),
        descripcion: ExcelUI.findColumnIndex(headers, ['descripcion']),
        estado: ExcelUI.findColumnIndex(headers, ['estado']),
        color: ExcelUI.findColumnIndex(headers, ['color']),
        condiciones: ExcelUI.findColumnIndex(headers, ['condiciones']),
        fecha: ExcelUI.findColumnIndex(headers, ['fecha ingreso', 'fecha_ingreso']),
        localizacion: ExcelUI.findColumnIndex(headers, ['localizacion', 'localizacion']),
    };

    const rows = [];

    jsonData.slice(1).forEach((row, index) => {
        const bien = String(row[idx.bien] ?? '').trim();

        if (!bien || bien.toLowerCase() === 'n/a' || bien.startsWith('*')) return;

        rows.push({
            bien,
            tipo: idx.tipo >= 0 ? String(row[idx.tipo] ?? 'Serial').trim() : 'Serial',
            serial: idx.serial >= 0 ? String(row[idx.serial] ?? '').trim() : '',
            cantidad: idx.cantidad >= 0 ? String(row[idx.cantidad] ?? '1').trim() : '1',
            marca: idx.marca >= 0 ? String(row[idx.marca] ?? '').trim() : '',
            modelo: idx.modelo >= 0 ? String(row[idx.modelo] ?? '').trim() : '',
            descripcion: idx.descripcion >= 0 ? String(row[idx.descripcion] ?? '').trim() : '',
            estado: idx.estado >= 0 ? String(row[idx.estado] ?? 'activo').trim() : 'activo',
            color: idx.color >= 0 ? String(row[idx.color] ?? '').trim() : '',
            condiciones: idx.condiciones >= 0 ? String(row[idx.condiciones] ?? '').trim() : '',
            fecha_ingreso: idx.fecha >= 0 ? ExcelUI.normalizeDate(row[idx.fecha]) : '',
            localizacion: idx.localizacion >= 0 ? String(row[idx.localizacion] ?? '').trim() : '',
            _rowNum: index + 2,
        });
    });

    return rows;
}

function locMapRow(row, tr) {
    if (!row.bien) return null;

    const esSerial = row.tipo === 'Serial';
    const mapped = {
        bien: row.bien,
        tipo: row.tipo,
        cantidad: esSerial ? null : (row.cantidad || tr?.dataset[locCacheKey('cantidad')] || '1'),
        localizacion: row.localizacion ?? '',
    };

    LOC_SERIAL_FIELDS.forEach((field) => {
        mapped[field] = esSerial ? (row[field] ?? tr?.dataset[locCacheKey(field)] ?? '') : null;
    });

    if (esSerial) {
        mapped.estado ||= 'activo';
        mapped.fecha_ingreso = ExcelUI.normalizeDate(mapped.fecha_ingreso);
    }

    return mapped;
}

function locLeerFilasDeDOM() {
    return LOC_EXCEL_STATE.preview?.readRows(locMapRow) || [];
}

async function locPostRows(rows) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const response = await fetch('/api/goods-inventory/batchCreateByLocalizacion', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({ rows }),
    });

    return response.json();
}

function locIrAGrupos() {
    loadContent('/groups', { onSuccess: () => initGroupFunctions() });
}

function locAbrirFallidos(sentRows, data) {
    ExcelFailedRows.open({
        headers: LOC_PREVIEW_HEADERS,
        columns: LOC_EXCEL_COLUMNS,
        prepareRow: locPrepareRow,
        mapRow: locMapRow,
        submit: locPostRows,
        exportColumns: LOC_EXPORT_COLUMNS,
        sentRows,
        failures: data.failed_rows,
        created: data.created,
        onRender: (tbody, rows) => {
            bindTipoSelect(tbody);
            locSeedRowCaches(rows, tbody);
        },
        onClose: ({ created }) => {
            if (created > 0) locIrAGrupos();
        },
    });
}

async function locEnviarDatos() {
    const preview = LOC_EXCEL_STATE.preview;

    if (!(await AssetNameReview.confirm({ tbody: preview?.elements.tbody, field: 'bien' }))) return;

    const rows = locLeerFilasDeDOM();

    if (!rows.length) return;

    const button = preview?.elements.submitButton;

    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    }

    preview?.clearErrors();

    try {
        const data = await locPostRows(rows);
        showToast(data);

        if (data.failed_rows?.length) {
            locAbrirFallidos(rows, data);
            return;
        }

        if (data.errors && data.errors.length) {
            preview?.showErrors(data.errors);
        }

        if (data.success) {
            locIrAGrupos();
        }
    } catch (error) {
        console.error(error);
        showToast({ success: false, message: 'Error de conexion.' });
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
