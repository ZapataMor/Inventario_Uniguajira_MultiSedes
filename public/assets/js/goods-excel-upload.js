const GOODS_EXCEL_CONFIG = {
    validTypes: ['.xlsx', '.xls', '.csv'],
    typeOptions: [
        { value: '', label: 'Seleccione' },
        { value: 'Cantidad', label: 'Cantidad' },
        { value: 'Serial', label: 'Serial' },
    ],
};

const GOODS_EXCEL_STATE = {
    preview: null,
    existingGoods: new Set(),
};

const GOODS_EXCEL_COLUMNS = [
    { field: 'nombre', type: 'text', title: 'Nombre del bien' },
    {
        field: 'tipo',
        type: 'select',
        value: ({ values }) => values.tipo,
        options: GOODS_EXCEL_CONFIG.typeOptions,
    },
    {
        field: 'estado',
        render: () => '<span data-preview-status="true" class="inline-flex min-w-[72px] items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Pendiente</span>',
    },
    { type: 'remove', align: 'center', title: 'Eliminar fila' },
];

function goodsExcelNormalizeType(value) {
    const normalized = String(value ?? '').trim().toLowerCase();

    if (normalized === '1' || normalized === 'cantidad') {
        return 'Cantidad';
    }

    if (normalized === '2' || normalized === 'serial') {
        return 'Serial';
    }

    return '';
}

function goodsExcelNormalizeName(value) {
    return String(value ?? '').trim().toLowerCase();
}

function goodsExcelPrepareRow(row) {
    return {
        nombre: String(row.nombre ?? '').trim(),
        tipo: goodsExcelNormalizeType(row.tipo),
    };
}

function initGoodsExcelUpload() {
    GOODS_EXCEL_STATE.preview = ExcelUI.createPreviewManager({
        tableId: 'goodsPreviewTable',
        bodyId: 'excel-preview-body',
        clearButtonId: 'btnLimpiarExcel',
        submitButtonId: 'btnEnviarExcel',
        errorListId: 'goodsErrorList',
        errorItemsId: 'goodsErrorItems',
        prepareRow: goodsExcelPrepareRow,
        columns: GOODS_EXCEL_COLUMNS,
        onClear: btnClearExcelUploadUI,
        onSubmit: sendGoodsData,
        onRowRemoved: () => goodsExcelRefreshPreviewStatus({ clearErrors: false }),
    });

    ExcelUI.initUploadArea({
        areaId: 'excel-upload-area',
        inputId: 'excelFileInput',
        onFileSelected: (files) => {
            goodsExcelHandleFile(files[0]);
        },
    });

    goodsExcelBindLiveValidation();
    btnClearExcelUploadUI();
}

async function goodsExcelHandleFile(file) {
    if (!file) return;

    if (!ExcelUI.hasValidExtension(file.name, GOODS_EXCEL_CONFIG.validTypes)) {
        showToast({ success: false, message: 'Formato invalido. Use .xlsx, .xls o .csv.' });
        return;
    }

    try {
        const jsonData = await ExcelUI.readExcelFile(file);
        const rows = goodsExcelParseRows(jsonData);

        if (rows === null) return;

        if (!rows.length) {
            showToast({ success: false, message: 'No se encontraron filas para previsualizar.' });
            return;
        }

        GOODS_EXCEL_STATE.existingGoods = await goodsExcelFetchExistingGoods();
        GOODS_EXCEL_STATE.preview?.renderRows(rows);
        goodsExcelHydrateRowMetadata(rows);
        goodsExcelRefreshPreviewStatus();

        if (!goodsExcelHasTypeColumn(jsonData)) {
            showToast({
                success: false,
                message: 'La columna "Tipo" no fue encontrada. Puedes completarla en la previsualizacion.',
            });
            return;
        }

        showToast({ success: true, message: `${rows.length} fila(s) lista(s) para revisar.` });
    } catch (error) {
        console.error('Error al procesar Excel:', error);
        showToast({ success: false, message: 'Error al procesar el archivo.' });
    }
}

function goodsExcelHasTypeColumn(jsonData) {
    if (!jsonData.length) return false;

    const headers = ExcelUI.normalizeHeaders(jsonData[0]);
    return ExcelUI.findColumnIndex(headers, ['tipo']) >= 0;
}

function goodsExcelParseRows(jsonData) {
    if (!jsonData.length) return [];

    const headers = ExcelUI.normalizeHeaders(jsonData[0]);
    const nameIndex = ExcelUI.findColumnIndex(headers, ['nombre', 'bien']);
    const typeIndex = ExcelUI.findColumnIndex(headers, ['tipo']);

    if (nameIndex < 0) {
        showToast({ success: false, message: 'El archivo debe contener la columna "Nombre".' });
        return null;
    }

    const rows = [];

    jsonData.slice(1).forEach((row, index) => {
        const values = Array.isArray(row) ? row : [];
        const hasContent = values.some((cell) => ExcelUI.normalizeText(cell) !== '');

        if (!hasContent) return;

        const tipoOriginal = typeIndex >= 0 ? ExcelUI.normalizeText(values[typeIndex]) : '';

        rows.push({
            nombre: ExcelUI.normalizeText(values[nameIndex]),
            tipo: goodsExcelNormalizeType(tipoOriginal),
            tipoOriginal,
            sourceRowNumber: index + 2,
        });
    });

    return rows;
}

async function goodsExcelFetchExistingGoods() {
    try {
        const response = await fetch('/api/goods/get/json', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Error al consultar los bienes existentes.');
        }

        const data = await response.json();

        return new Set(
            data
                .map((item) => goodsExcelNormalizeName(item.bien))
                .filter(Boolean)
        );
    } catch (error) {
        console.error('Error al obtener bienes existentes:', error);
        return new Set();
    }
}

function goodsExcelHydrateRowMetadata(rows) {
    const tbody = GOODS_EXCEL_STATE.preview?.elements.tbody;
    if (!tbody) return;

    Array.from(tbody.querySelectorAll('tr')).forEach((tr, index) => {
        const row = rows[index] || {};
        tr.dataset.originalTypeInput = String(row.tipoOriginal ?? '');
        tr.dataset.sourceRowNumber = String(row.sourceRowNumber ?? index + 2);
    });
}

function goodsExcelReadDraftRows() {
    const tbody = GOODS_EXCEL_STATE.preview?.elements.tbody;
    if (!tbody) return [];

    return Array.from(tbody.querySelectorAll('tr')).map((tr, index) => {
        const nameElement = tr.querySelector('[data-field="nombre"]');
        const typeElement = tr.querySelector('[data-field="tipo"]');

        return {
            index,
            tr,
            nombre: ExcelUI.normalizeText(nameElement?.textContent ?? ''),
            tipo: goodsExcelNormalizeType(typeElement?.value ?? ''),
            originalTypeInput: String(tr.dataset.originalTypeInput ?? ''),
            rowNumber: Number(tr.dataset.sourceRowNumber || index + 2),
        };
    });
}

function goodsExcelBuildDiagnostics(rows) {
    const counts = new Map();

    rows.forEach((row) => {
        const key = goodsExcelNormalizeName(row.nombre);
        if (!key) return;
        counts.set(key, (counts.get(key) || 0) + 1);
    });

    return rows.map((row) => {
        const blocking = [];
        const warnings = [];
        const key = goodsExcelNormalizeName(row.nombre);

        if (!row.nombre) {
            blocking.push('Nombre obligatorio');
        }

        if (!row.tipo) {
            if (row.originalTypeInput) {
                blocking.push(`Tipo invalido: ${row.originalTypeInput}`);
            } else {
                blocking.push('Tipo obligatorio');
            }
        }

        if (key) {
            if (GOODS_EXCEL_STATE.existingGoods.has(key)) {
                warnings.push('Ya existe en el catalogo');
            }

            if ((counts.get(key) || 0) > 1) {
                warnings.push('Nombre repetido en el archivo');
            }
        }

        return {
            blocking,
            warnings,
            all: [...blocking, ...warnings],
        };
    });
}

function goodsExcelRefreshPreviewStatus(options = {}) {
    const preview = GOODS_EXCEL_STATE.preview;
    if (!preview) {
        return { rows: [], diagnostics: [] };
    }

    const rows = goodsExcelReadDraftRows();
    const diagnostics = goodsExcelBuildDiagnostics(rows);

    rows.forEach((row, index) => {
        const diagnostic = diagnostics[index];
        const tr = row.tr;
        const statusElement = tr.querySelector('[data-preview-status="true"]');
        const nameElement = tr.querySelector('[data-field="nombre"]');
        const typeElement = tr.querySelector('[data-field="tipo"]');

        tr.classList.remove('bg-rose-50/80', 'bg-amber-50/70');
        nameElement?.classList.remove('border-rose-300', 'bg-rose-50');
        typeElement?.classList.remove('border-rose-300', 'bg-rose-50', 'focus:border-rose-400', 'focus:ring-rose-100');

        if (!statusElement) return;

        let label = 'Lista';
        let className = 'inline-flex min-w-[72px] items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700';

        if (diagnostic.blocking.length) {
            label = diagnostic.blocking.join(' | ');
            className = 'inline-flex min-w-[72px] items-center rounded-full bg-rose-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-rose-700';
            tr.classList.add('bg-rose-50/80');

            if (diagnostic.blocking.some((item) => item.startsWith('Nombre'))) {
                nameElement?.classList.add('border-rose-300', 'bg-rose-50');
            }

            if (diagnostic.blocking.some((item) => item.startsWith('Tipo'))) {
                typeElement?.classList.add('border-rose-300', 'bg-rose-50', 'focus:border-rose-400', 'focus:ring-rose-100');
            }
        } else if (diagnostic.warnings.length) {
            label = diagnostic.warnings.join(' | ');
            className = 'inline-flex min-w-[72px] items-center rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-amber-800';
            tr.classList.add('bg-amber-50/70');
        }

        statusElement.textContent = label;
        statusElement.className = className;
        statusElement.title = diagnostic.all.join(' | ');
    });

    goodsExcelUpdateSubmitState(diagnostics);

    if (options.clearErrors !== false) {
        preview.clearErrors();
    }

    return { rows, diagnostics };
}

function goodsExcelUpdateSubmitState(diagnostics) {
    const button = GOODS_EXCEL_STATE.preview?.elements.submitButton;
    if (!button) return;

    const hasRows = diagnostics.length > 0;
    const hasSendableRows = diagnostics.some((item) => item.blocking.length === 0);

    button.disabled = !hasRows || !hasSendableRows;
}

function goodsExcelBindLiveValidation() {
    const tbody = GOODS_EXCEL_STATE.preview?.elements.tbody;
    if (!tbody || tbody.dataset.goodsExcelBound === '1') return;

    tbody.addEventListener('input', (event) => {
        const target = event.target;
        if (!target.closest('[data-field="nombre"]')) return;

        goodsExcelRefreshPreviewStatus({ clearErrors: false });
    });

    tbody.addEventListener('change', (event) => {
        const target = event.target;
        if (!target.matches('[data-field="tipo"]')) return;

        const row = target.closest('tr');
        if (row) {
            row.dataset.originalTypeInput = '';
        }

        goodsExcelRefreshPreviewStatus({ clearErrors: false });
    });

    tbody.dataset.goodsExcelBound = '1';
}

async function sendGoodsData() {
    const preview = GOODS_EXCEL_STATE.preview;
    if (!preview) return;

    const { rows, diagnostics } = goodsExcelRefreshPreviewStatus({ clearErrors: false });
    const blockingErrors = diagnostics.flatMap((diagnostic, index) =>
        diagnostic.blocking.map((issue) => `Fila ${rows[index].rowNumber}: ${issue}.`)
    );

    if (blockingErrors.length) {
        preview.showErrors(blockingErrors);
        showToast({
            success: false,
            message: 'Corrige o descarta las filas marcadas antes de enviar.',
        });
        return;
    }

    const payload = rows.map((row) => ({
        nombre: row.nombre,
        tipo: row.tipo,
    }));

    if (!payload.length) {
        showToast({ success: false, message: 'No hay filas validas para enviar.' });
        return;
    }

    const button = preview.elements.submitButton;
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    }

    preview.clearErrors();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('/api/goods/batchCreate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ goods: payload }),
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error('El servidor respondio con un formato invalido.');
        }

        const data = await response.json();
        showToast(data);

        if (data.errors && data.errors.length) {
            preview.showErrors(data.errors);
        }

        if (data.success && (!data.errors || !data.errors.length)) {
            window.globalAutocomplete?.recargarDatos();
            loadContent('/goods', {
                onSuccess: () => {
                    if (typeof initFormsBien === 'function') {
                        initFormsBien();
                    }
                },
            });
            btnClearExcelUploadUI();
        }
    } catch (error) {
        console.error('Error al enviar datos:', error);
        showToast({
            success: false,
            message: error.message || 'Error de conexion.',
        });
    } finally {
        if (button) {
            button.innerHTML = 'Enviar';
        }

        goodsExcelRefreshPreviewStatus({ clearErrors: false });
    }
}

function btnClearExcelUploadUI() {
    ExcelUI.resetFileInput('excelFileInput');
    GOODS_EXCEL_STATE.preview?.clear();
}

function handleFileUpload(event) {
    const file = event?.target?.files?.[0];
    if (!file) return;

    goodsExcelHandleFile(file);
}

window.initGoodsExcelUpload = initGoodsExcelUpload;
window.handleFileUpload = handleFileUpload;
window.sendGoodsData = sendGoodsData;
window.btnClearExcelUploadUI = btnClearExcelUploadUI;
