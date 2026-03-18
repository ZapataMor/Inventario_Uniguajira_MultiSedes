// Carga masiva de bienes al catalogo global con asignacion opcional a inventario.

const GLOBAL_EXCEL_CONFIG = {
    validTypes: ['.xlsx', '.xls'],
};

const GLOBAL_EXCEL_STATE = {
    preview: null,
};

const GLOBAL_EXCEL_COLUMNS = [
    { field: 'bien', type: 'text' },
    { field: 'tipo', type: 'static', value: ({ values }) => values.tipo, textStyle: 'font-size:0.85rem;' },
    { field: 'localizacion', type: 'text', title: 'Nombre del inventario (opcional)' },
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
    { type: 'remove', align: 'center', padding: '4px 10px', title: 'Eliminar fila' },
];

function globalExcelPrepareRow(row) {
    const tipo = String(row.tipo ?? 'Serial').trim().toLowerCase() === 'cantidad' ? 'Cantidad' : 'Serial';

    return {
        bien: String(row.bien ?? '').trim(),
        tipo,
        esSerial: tipo === 'Serial',
        localizacion: String(row.localizacion ?? '').trim(),
        serial: String(row.serial ?? '').trim(),
        cantidad: String(row.cantidad ?? '1').trim() || '1',
        marca: String(row.marca ?? '').trim(),
        modelo: String(row.modelo ?? '').trim(),
        estado: String(row.estado ?? '').trim() === 'inactivo' ? 'inactivo' : 'activo',
    };
}

function initFormsGlobalExcel() {
    GLOBAL_EXCEL_STATE.preview = ExcelUI.createPreviewManager({
        tableId: 'globalPreviewTable',
        bodyId: 'globalPreviewBody',
        clearButtonId: 'btnLimpiarExcelGlobal',
        submitButtonId: 'btnEnviarExcelGlobal',
        errorListId: 'globalErrorList',
        errorItemsId: 'globalErrorItems',
        prepareRow: globalExcelPrepareRow,
        columns: GLOBAL_EXCEL_COLUMNS,
        onClear: globalExcelLimpiarUI,
        onSubmit: globalExcelEnviarDatos,
    });

    ExcelUI.initUploadArea({
        areaId: 'global-excel-upload-area',
        inputId: 'globalExcelFileInput',
        onFileSelected: (files) => {
            globalExcelHandleFile(files[0]);
        },
    });

    globalExcelLimpiarUI();
}

async function globalExcelHandleFile(file) {
    if (!file) return;

    if (!ExcelUI.hasValidExtension(file.name, GLOBAL_EXCEL_CONFIG.validTypes)) {
        showToast({ success: false, message: 'Formato invalido. Use .xlsx o .xls' });
        return;
    }

    try {
        const jsonData = await ExcelUI.readExcelFile(file);
        const rows = globalExcelParsearFilas(jsonData);

        if (!rows) return;

        if (!rows.length) {
            showToast({ success: false, message: 'No se encontraron datos validos.' });
            return;
        }

        GLOBAL_EXCEL_STATE.preview?.renderRows(rows);
        showToast({ success: true, message: `${rows.length} fila(s) lista(s) para enviar.` });
    } catch (error) {
        console.error(error);
        showToast({ success: false, message: 'Error al leer el archivo Excel.' });
    }
}

function globalExcelParsearFilas(jsonData) {
    if (!jsonData.length) return [];

    const headers = ExcelUI.normalizeHeaders(jsonData[0]);

    if (!headers.includes('bien')) {
        showToast({ success: false, message: 'El archivo debe tener al menos la columna "Bien".' });
        return null;
    }

    const idx = {
        bien: ExcelUI.findColumnIndex(headers, ['bien']),
        tipo: ExcelUI.findColumnIndex(headers, ['tipo']),
        localizacion: ExcelUI.findColumnIndex(headers, ['localizacion']),
        serial: ExcelUI.findColumnIndex(headers, ['serial']),
        cantidad: ExcelUI.findColumnIndex(headers, ['cantidad']),
        marca: ExcelUI.findColumnIndex(headers, ['marca']),
        modelo: ExcelUI.findColumnIndex(headers, ['modelo']),
        descripcion: ExcelUI.findColumnIndex(headers, ['descripcion']),
        estado: ExcelUI.findColumnIndex(headers, ['estado']),
        color: ExcelUI.findColumnIndex(headers, ['color']),
        condiciones: ExcelUI.findColumnIndex(headers, ['condiciones']),
        fecha: ExcelUI.findColumnIndex(headers, ['fecha ingreso', 'fecha_ingreso']),
    };

    const rows = [];

    jsonData.slice(1).forEach((row) => {
        const bien = String(row[idx.bien] ?? '').trim();
        if (!bien || bien.toLowerCase() === 'n/a') return;

        rows.push({
            bien,
            tipo: idx.tipo >= 0 ? String(row[idx.tipo] ?? 'Serial').trim() : 'Serial',
            localizacion: idx.localizacion >= 0 ? String(row[idx.localizacion] ?? '').trim() : '',
            serial: idx.serial >= 0 ? String(row[idx.serial] ?? '').trim() : '',
            cantidad: idx.cantidad >= 0 ? String(row[idx.cantidad] ?? '1').trim() : '1',
            marca: idx.marca >= 0 ? String(row[idx.marca] ?? '').trim() : '',
            modelo: idx.modelo >= 0 ? String(row[idx.modelo] ?? '').trim() : '',
            descripcion: idx.descripcion >= 0 ? String(row[idx.descripcion] ?? '').trim() : '',
            estado: idx.estado >= 0 ? String(row[idx.estado] ?? 'activo').trim() : 'activo',
            color: idx.color >= 0 ? String(row[idx.color] ?? '').trim() : '',
            condiciones: idx.condiciones >= 0 ? String(row[idx.condiciones] ?? '').trim() : '',
            fecha_ingreso: idx.fecha >= 0 ? String(row[idx.fecha] ?? '').trim() : '',
        });
    });

    return rows;
}

function globalExcelLeerFilasDeDOM() {
    return GLOBAL_EXCEL_STATE.preview?.readRows((row) => {
        if (!row.bien) return null;

        const esSerial = row.tipo === 'Serial';

        return {
            bien: row.bien,
            tipo: row.tipo,
            localizacion: row.localizacion ?? '',
            serial: esSerial ? (row.serial ?? '') : null,
            cantidad: esSerial ? null : (row.cantidad || '1'),
            marca: row.marca ?? '',
            modelo: row.modelo ?? '',
            estado: row.estado ?? 'activo',
        };
    }) || [];
}

function globalExcelResaltarErroresLocalizacion(errors) {
    const preview = GLOBAL_EXCEL_STATE.preview;
    if (!preview) return;

    const locations = errors
        .map((error) => error.match(/inventario '([^']+)' no encontrado/i))
        .filter(Boolean)
        .map((match) => match[1].trim().toLowerCase());

    preview.clearHighlights();

    preview.highlightRows((tr) => {
        const locationElement = tr.querySelector('[data-field="localizacion"]');
        const location = locationElement ? locationElement.textContent.trim().toLowerCase() : '';
        return locations.includes(location);
    }, {
        field: 'localizacion',
        title: 'Esta localizacion no existe en el sistema.',
    });
}

async function globalExcelEnviarDatos() {
    const rows = globalExcelLeerFilasDeDOM();
    if (!rows.length) return;

    const preview = GLOBAL_EXCEL_STATE.preview;
    const button = preview?.elements.submitButton;

    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    }

    preview?.clearErrors();
    preview?.clearHighlights();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch('/api/goods/batchCreateGlobal', {
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
            preview?.showErrors(data.errors, {
                emphasize: (error) => /no encontrado/i.test(error),
            });

            const locationErrors = data.errors.filter((error) => /no encontrado/i.test(error));
            if (locationErrors.length) {
                globalExcelResaltarErroresLocalizacion(locationErrors);
            }
        }

        if (data.success) {
            const hasLocationErrors = (data.errors || []).some((error) => /no encontrado/i.test(error));
            if (!hasLocationErrors) {
                loadContent('/goods', {
                    onSuccess: () => {
                        if (typeof initFormsBien === 'function') initFormsBien();
                    },
                });
                globalExcelLimpiarUI();
            }
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

function globalExcelDescargarPlantilla() {
    const workbook = XLSX.utils.book_new();

    const headers = [
        'Bien', 'Tipo', 'Localizacion', 'Serial', 'Cantidad',
        'Marca', 'Modelo', 'Descripcion', 'Estado', 'Color', 'Condiciones', 'Fecha Ingreso',
    ];

    const ejemplos = [
        ['AIRE ACONDICIONADO MINI SPLIT', 'Serial', 'Sala de Sistemas', 'ABC-001', '', 'Samsung', 'AS24UBAN', '', 'activo', 'Blanco', 'Buen estado', new Date().toISOString().split('T')[0]],
        ['SILLA ERGONOMICA', 'Cantidad', 'Sala de Profesores', '', '5', 'Rimax', '', '', '', '', '', ''],
        ['COMPUTADOR PORTATIL', 'Serial', '', 'XYZ-002', '', 'Lenovo', 'ThinkPad', '', 'activo', 'Negro', '', ''],
    ];

    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...ejemplos]);
    worksheet['!cols'] = [
        { wch: 30 }, { wch: 12 }, { wch: 25 }, { wch: 18 }, { wch: 10 },
        { wch: 16 }, { wch: 16 }, { wch: 25 }, { wch: 18 }, { wch: 12 }, { wch: 25 }, { wch: 14 },
    ];

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Plantilla');
    XLSX.writeFile(workbook, 'Plantilla_Carga_Global.xlsx');
}

function globalExcelLimpiarUI() {
    ExcelUI.resetFileInput('globalExcelFileInput');
    GLOBAL_EXCEL_STATE.preview?.clear();
}

window.initFormsGlobalExcel = initFormsGlobalExcel;
window.globalExcelDescargarPlantilla = globalExcelDescargarPlantilla;
