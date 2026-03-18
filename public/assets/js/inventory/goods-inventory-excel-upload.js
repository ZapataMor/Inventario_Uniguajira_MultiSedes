// Carga masiva de bienes a un inventario especifico desde Excel.

const INV_EXCEL_CONFIG = {
    validTypes: ['.xlsx', '.xls'],
    requiredHeaders: ['bien', 'tipo'],
};

const INV_EXCEL_STATE = {
    preview: null,
};

const INV_EXCEL_COLUMNS = [
    { field: 'bien', type: 'text' },
    { field: 'tipo', type: 'static', value: ({ values }) => values.tipo, textStyle: 'font-size:0.83rem;' },
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

function invPrepareRow(row) {
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
    };
}

function btnAbrirModalExcelInventario() {
    const inventory = document.getElementById('inventory-name');
    if (!inventory) return;

    const groupId = inventory.getAttribute('data-group-id');
    const inventoryId = inventory.getAttribute('data-id');

    loadContent(
        `/group/${groupId}/inventory/${inventoryId}/excel-upload`,
        { onSuccess: () => initInventoryExcelUploadView() }
    );
}

function initInventoryExcelUploadView() {
    INV_EXCEL_STATE.preview = ExcelUI.createPreviewManager({
        tableId: 'invPreviewTable',
        bodyId: 'invPreviewBody',
        clearButtonId: 'btnLimpiarExcelInventario',
        submitButtonId: 'btnEnviarExcelInventario',
        errorListId: 'invErrorList',
        errorItemsId: 'invErrorItems',
        prepareRow: invPrepareRow,
        columns: INV_EXCEL_COLUMNS,
        onClear: invLimpiarUI,
        onSubmit: invEnviarDatos,
    });

    ExcelUI.initUploadArea({
        areaId: 'inv-excel-upload-area',
        inputId: 'invExcelFileInput',
        onFileSelected: (files) => {
            invHandleFile(files[0]);
        },
    });

    invLimpiarUI();
}

async function invHandleFile(file) {
    if (!file) return;

    if (!ExcelUI.hasValidExtension(file.name, INV_EXCEL_CONFIG.validTypes)) {
        showToast({ success: false, message: 'Formato invalido. Use .xlsx o .xls' });
        return;
    }

    try {
        const jsonData = await ExcelUI.readExcelFile(file);
        const rows = invParsearFilas(jsonData);

        if (!rows.length) {
            showToast({ success: false, message: 'No se encontraron datos validos.' });
            return;
        }

        INV_EXCEL_STATE.preview?.renderRows(rows);
        showToast({ success: true, message: `${rows.length} fila(s) lista(s) para enviar.` });
    } catch (error) {
        console.error(error);
        showToast({ success: false, message: 'Error al leer el archivo Excel.' });
    }
}

function invParsearFilas(jsonData) {
    if (!jsonData.length) return [];

    const headers = ExcelUI.normalizeHeaders(jsonData[0]);
    const hasRequired = INV_EXCEL_CONFIG.requiredHeaders.every((header) => headers.includes(header));

    if (!hasRequired) {
        showToast({
            success: false,
            message: `El archivo debe tener al menos las columnas: ${INV_EXCEL_CONFIG.requiredHeaders.join(', ')}`,
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
    };

    const rows = [];

    jsonData.slice(1).forEach((row, index) => {
        const bien = String(row[idx.bien] ?? '').trim();
        if (!bien || bien.toLowerCase() === 'n/a') return;

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
            fecha_ingreso: idx.fecha >= 0 ? String(row[idx.fecha] ?? '').trim() : '',
            _rowNum: index + 2,
        });
    });

    return rows;
}

function invLeerFilasDeDOM() {
    return INV_EXCEL_STATE.preview?.readRows((row) => {
        if (!row.bien) return null;

        const esSerial = row.tipo === 'Serial';

        return {
            bien: row.bien,
            tipo: row.tipo,
            serial: esSerial ? (row.serial ?? '') : null,
            cantidad: esSerial ? null : (row.cantidad || '1'),
            marca: row.marca ?? '',
            modelo: row.modelo ?? '',
            estado: row.estado ?? 'activo',
        };
    }) || [];
}

async function invEnviarDatos() {
    const rows = invLeerFilasDeDOM();
    const inventoryId = document.getElementById('inventory-name')?.getAttribute('data-id');

    if (!rows.length || !inventoryId) return;

    const preview = INV_EXCEL_STATE.preview;
    const button = preview?.elements.submitButton;

    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    }

    preview?.clearErrors();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch(`/api/goods-inventory/batchCreate/${inventoryId}`, {
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
            const groupId = document.getElementById('inventory-name')?.getAttribute('data-group-id');
            loadContent(
                `/group/${groupId}/inventory/${inventoryId}`,
                { onSuccess: () => initGoodsInventoryFunctions() }
            );
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

function descargarPlantillaInventario() {
    window.location.href = '/api/goods-inventory/download-template';
}

function invLimpiarUI() {
    ExcelUI.resetFileInput('invExcelFileInput');
    INV_EXCEL_STATE.preview?.clear();
}

window.btnAbrirModalExcelInventario = btnAbrirModalExcelInventario;
window.descargarPlantillaInventario = descargarPlantillaInventario;
window.initInventoryExcelUploadView = initInventoryExcelUploadView;
