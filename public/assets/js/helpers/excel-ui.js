(function () {
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeText(value) {
        return String(value ?? '').trim();
    }

    function toIsoDate(year, month, day) {
        const [y, m, d] = [Number(year), Number(month), Number(day)];
        const date = new Date(Date.UTC(y, m - 1, d));

        if (date.getUTCFullYear() !== y || date.getUTCMonth() !== m - 1 || date.getUTCDate() !== d) {
            return null;
        }

        return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    // Convierte fechas de Excel (numero de serie, AAAA-MM-DD o DD/MM/AAAA) a AAAA-MM-DD.
    // Lo que no se reconoce se devuelve tal cual para que el backend lo rechace
    // y el usuario lo vea marcado.
    function normalizeDate(value) {
        if (value instanceof Date && !Number.isNaN(value.getTime())) {
            return toIsoDate(value.getFullYear(), value.getMonth() + 1, value.getDate());
        }

        const text = normalizeText(value);
        if (!text) return '';

        if (/^\d+(\.\d+)?$/.test(text)) {
            // Al minuto: algunas herramientas guardan la medianoche como 45932.9998.
            const serial = Math.round(Number(text) * 1440) / 1440;
            const parsed = serial >= 1 && serial < 2958466 ? window.XLSX?.SSF?.parse_date_code(serial) : null;
            return (parsed && toIsoDate(parsed.y, parsed.m, parsed.d)) || text;
        }

        let match = text.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/);
        if (match) return toIsoDate(match[1], match[2], match[3]) || text;

        match = text.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})$/);
        if (match) return toIsoDate(match[3], match[2], match[1]) || text;

        return text;
    }

    function getExtension(fileName) {
        const index = String(fileName ?? '').lastIndexOf('.');
        return index >= 0 ? fileName.substring(index).toLowerCase() : '';
    }

    function hasValidExtension(fileName, validTypes) {
        return validTypes.includes(getExtension(fileName));
    }

    function normalizeHeaders(headerRow) {
        return (headerRow || []).map((header) =>
            normalizeText(header)
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\*/g, '')
        );
    }

    function findColumnIndex(headers, candidates) {
        for (const candidate of candidates) {
            const index = headers.indexOf(candidate);
            if (index >= 0) return index;
        }

        return -1;
    }

    function resetFileInput(inputId) {
        const input = document.getElementById(inputId);
        if (input) input.value = '';
    }

    function readExcelFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = (event) => {
                try {
                    const workbook = XLSX.read(event.target.result, { type: 'binary' });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
                    resolve(rows);
                } catch (error) {
                    reject(error);
                }
            };

            reader.onerror = () => reject(new Error('No se pudo leer el archivo.'));
            reader.readAsBinaryString(file);
        });
    }

    function initUploadArea(config) {
        const area = document.getElementById(config.areaId);
        const input = document.getElementById(config.inputId);
        const dragClasses = [
            'border-emerald-500',
            'bg-emerald-50/90',
            'ring-4',
            'ring-emerald-100',
            'shadow-[0_25px_55px_-35px_rgba(22,163,74,0.55)]',
            '-translate-y-0.5',
        ];

        if (!area || !input) return null;

        if (area.dataset.excelUploadReady === '1') return { area, input };

        const openPicker = () => input.click();
        const toggleDragState = (enabled) => {
            area.classList[enabled ? 'add' : 'remove'](...dragClasses);
        };
        const handleFiles = (files, source, event) => {
            if (!files || !files.length || typeof config.onFileSelected !== 'function') return;
            config.onFileSelected(Array.from(files), { source, event, area, input });
        };

        area.addEventListener('click', () => openPicker());

        area.querySelectorAll('[data-excel-select-trigger]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                openPicker();
            });
        });

        input.addEventListener('change', (event) => {
            handleFiles(event.target.files, 'input', event);
        });

        area.addEventListener('dragover', (event) => {
            event.preventDefault();
            toggleDragState(true);
        });

        area.addEventListener('dragleave', () => {
            toggleDragState(false);
        });

        area.addEventListener('drop', (event) => {
            event.preventDefault();
            toggleDragState(false);

            const files = event.dataTransfer?.files;
            if (!files || !files.length) return;

            try {
                input.files = files;
            } catch (error) {
                // Algunos navegadores no permiten asignar FileList manualmente.
            }

            handleFiles(files, 'drop', event);
        });

        area.dataset.excelUploadReady = '1';

        return { area, input };
    }

    function createPreviewManager(config) {
        const table = document.getElementById(config.tableId);
        const tbody = document.getElementById(config.bodyId);
        const clearButton = config.clearButtonId ? document.getElementById(config.clearButtonId) : null;
        const submitButton = config.submitButtonId ? document.getElementById(config.submitButtonId) : null;
        const errorList = config.errorListId ? document.getElementById(config.errorListId) : null;
        const errorItems = config.errorItemsId ? document.getElementById(config.errorItemsId) : null;

        if (!table || !tbody) return null;

        const cellHelpers = {
            editable(field, value, options = {}) {
                const attrs = [];
                if (options.title) attrs.push(`title="${escapeHtml(options.title)}"`);
                return `<div class="excel-preview-edit-cell block min-w-[72px] w-full rounded-xl border border-transparent bg-white px-3 py-2 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-emerald-500 focus:bg-emerald-50/70 focus:ring-4 focus:ring-emerald-100" contenteditable="plaintext-only" data-field="${escapeHtml(field)}" ${attrs.join(' ')}>${escapeHtml(value)}</div>`;
            },
            static(field, value, options = {}) {
                const attrs = [];
                if (options.title) attrs.push(`title="${escapeHtml(options.title)}"`);
                return `<span class="inline-flex min-w-[72px] items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600" data-field="${escapeHtml(field)}" ${attrs.join(' ')}>${escapeHtml(value)}</span>`;
            },
            placeholder(value = '-') {
                return `<span class="excel-preview-disabled inline-flex min-w-[72px] items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium italic text-slate-400">${escapeHtml(value)}</span>`;
            },
            select(field, value, choices = []) {
                const options = choices.map((choice) => {
                    const selected = String(choice.value) === String(value) ? 'selected' : '';
                    return `<option value="${escapeHtml(choice.value)}" ${selected}>${escapeHtml(choice.label)}</option>`;
                }).join('');

                return `<select class="excel-preview-edit-select w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none transition hover:border-slate-300 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100" data-field="${escapeHtml(field)}">${options}</select>`;
            },
            removeButton(title = 'Eliminar fila') {
                return `<button type="button" class="excel-preview-remove inline-flex h-9 w-9 items-center justify-center rounded-full text-rose-500 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-4 focus:ring-rose-100" data-excel-remove-row="true" title="${escapeHtml(title)}"><i class="fas fa-times"></i></button>`;
            },
        };

        function bindButtons() {
            if (clearButton && typeof config.onClear === 'function' && clearButton.dataset.excelBound !== '1') {
                clearButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    config.onClear();
                });
                clearButton.dataset.excelBound = '1';
            }

            if (submitButton && typeof config.onSubmit === 'function' && submitButton.dataset.excelBound !== '1') {
                submitButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    config.onSubmit();
                });
                submitButton.dataset.excelBound = '1';
            }

            if (tbody.dataset.excelBound === '1') return;

            tbody.addEventListener('click', (event) => {
                const button = event.target.closest('[data-excel-remove-row="true"]');
                if (!button) return;

                const row = button.closest('tr');
                if (row) row.remove();

                manager.updateSubmitButton();

                if (typeof config.onRowRemoved === 'function') {
                    config.onRowRemoved(row);
                }
            });

            tbody.dataset.excelBound = '1';
        }

        function buildCell(column, row, values, index) {
            if (typeof column.render === 'function') {
                return column.render({ row, values, index, helpers: cellHelpers });
            }

            const field = column.field;
            const value = typeof column.value === 'function'
                ? column.value({ row, values, index })
                : values[field];

            switch (column.type) {
                case 'static':
                    return cellHelpers.static(field, value ?? '', { title: column.title });

                case 'select':
                    return cellHelpers.select(field, value ?? '', column.options || []);

                case 'remove':
                    return cellHelpers.removeButton(column.title);

                case 'text':
                default:
                    return cellHelpers.editable(field, value ?? '', { title: column.title });
            }
        }

        const manager = {
            renderRows(rows) {
                tbody.innerHTML = '';

                rows.forEach((row, index) => {
                    const values = typeof config.prepareRow === 'function'
                        ? config.prepareRow({ ...row }, index)
                        : { ...row };

                    const tr = document.createElement('tr');
                    tr.classList.add('excel-preview-row', 'border-b', 'border-slate-100', 'transition-colors', 'hover:bg-slate-50/80');

                    (config.columns || []).forEach((column) => {
                        const td = document.createElement('td');
                        td.classList.add('px-4', 'py-3', 'align-middle', 'text-sm', 'text-slate-700');
                        if (column.align === 'center') td.classList.add('text-center');
                        if (column.align === 'right') td.classList.add('text-right');
                        if (column.type === 'remove') td.classList.add('w-14');
                        td.innerHTML = buildCell(column, row, values, index);
                        tr.appendChild(td);
                    });

                    tbody.appendChild(tr);
                });

                table.classList.toggle('hidden', rows.length === 0);
                manager.updateSubmitButton();
            },

            readRows(mapRow) {
                const rows = [];

                tbody.querySelectorAll('tr').forEach((tr) => {
                    const row = {};

                    tr.querySelectorAll('[data-field]').forEach((element) => {
                        row[element.dataset.field] = element.tagName === 'SELECT'
                            ? element.value
                            : normalizeText(element.textContent);
                    });

                    const mapped = typeof mapRow === 'function' ? mapRow(row, tr) : row;
                    if (mapped) rows.push(mapped);
                });

                return rows;
            },

            clear() {
                tbody.innerHTML = '';
                table.classList.add('hidden');
                manager.clearErrors();
                manager.clearHighlights();
                manager.updateSubmitButton();
            },

            updateSubmitButton() {
                if (!submitButton) return;
                submitButton.disabled = tbody.querySelectorAll('tr').length === 0;
            },

            showErrors(errors, options = {}) {
                if (!errorList || !errorItems) return;

                errorItems.innerHTML = '';

                errors.forEach((error) => {
                    const li = document.createElement('li');
                    li.className = 'rounded-xl border border-rose-100 bg-white/80 px-3 py-2 text-sm text-rose-700 shadow-sm';
                    li.textContent = error;
                    if (typeof options.emphasize === 'function' && options.emphasize(error)) {
                        li.classList.add('font-semibold');
                    }
                    errorItems.appendChild(li);
                });

                errorList.classList.toggle('hidden', errors.length === 0);
            },

            clearErrors() {
                if (!errorList || !errorItems) return;
                errorList.classList.add('hidden');
                errorItems.innerHTML = '';
            },

            clearHighlights() {
                tbody.querySelectorAll('tr').forEach((tr) => {
                    tr.classList.remove('bg-rose-50/80');
                    tr.querySelectorAll('[data-field]').forEach((element) => {
                        element.classList.remove('border-rose-300', 'bg-rose-50');
                        if (element.tagName === 'SELECT') {
                            element.classList.remove('focus:border-rose-400', 'focus:ring-rose-100');
                        }
                    });
                });
            },

            highlightRows(predicate, options = {}) {
                tbody.querySelectorAll('tr').forEach((tr, index) => {
                    if (!predicate(tr, index)) return;

                    tr.classList.add('bg-rose-50/80');

                    if (options.field) {
                        const fieldElement = tr.querySelector(`[data-field="${options.field}"]`);
                        if (fieldElement) {
                            fieldElement.classList.add('border-rose-300', 'bg-rose-50');
                            if (fieldElement.tagName === 'SELECT') {
                                fieldElement.classList.add('focus:border-rose-400', 'focus:ring-rose-100');
                            }
                            if (options.title) {
                                fieldElement.title = options.title;
                            }
                        }
                    }
                });
            },

            elements: {
                table,
                tbody,
                clearButton,
                submitButton,
                errorList,
                errorItems,
            },
        };

        bindButtons();
        manager.updateSubmitButton();

        return manager;
    }

    window.ExcelUI = {
        createPreviewManager,
        escapeHtml,
        findColumnIndex,
        getExtension,
        hasValidExtension,
        initUploadArea,
        normalizeDate,
        normalizeHeaders,
        normalizeText,
        readExcelFile,
        resetFileInput,
    };
})();
