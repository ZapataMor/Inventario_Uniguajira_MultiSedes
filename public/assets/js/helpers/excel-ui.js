(function () {
    const STYLE_ID = 'excel-ui-shared-styles';

    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) return;

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .excel-upload-area.is-dragging {
                border-color: #1B5E20 !important;
                background: #f0faf0;
            }
            .excel-preview-edit-cell {
                min-width: 60px;
                padding: 2px 5px;
                border-radius: 4px;
                border: 1px solid transparent;
                cursor: text;
                display: inline-block;
                width: 100%;
                box-sizing: border-box;
                font-size: 0.85rem;
            }
            .excel-preview-edit-cell:focus {
                border-color: #1B5E20;
                background: #f0faf0;
                outline: none;
                box-shadow: 0 0 0 2px #c8e6c9;
            }
            .excel-preview-edit-cell:hover {
                border-color: #ccc;
            }
            .excel-preview-disabled {
                min-width: 60px;
                padding: 2px 5px;
                font-size: 0.85rem;
                color: #bbb;
                font-style: italic;
            }
            .excel-preview-edit-select {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 2px 4px;
                font-size: 0.82rem;
                background: #fff;
                cursor: pointer;
                width: 100%;
            }
            .excel-preview-edit-select:focus {
                border-color: #1B5E20;
                outline: none;
            }
            .excel-preview-row:hover td {
                background: #fafafa;
            }
            .excel-preview-row-error td {
                background: #fff3f3 !important;
            }
            .excel-preview-row-error [data-field] {
                border-color: #c62828 !important;
                background: #ffebee !important;
            }
            .excel-preview-remove {
                cursor: pointer;
                color: #c62828;
                background: transparent;
                border: none;
                padding: 0;
            }
        `;

        document.head.appendChild(style);
    }

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
        ensureStyles();

        const area = document.getElementById(config.areaId);
        const input = document.getElementById(config.inputId);

        if (!area || !input) return null;

        if (area.dataset.excelUploadReady === '1') return { area, input };

        const openPicker = () => input.click();
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
            area.classList.add('is-dragging');
        });

        area.addEventListener('dragleave', () => {
            area.classList.remove('is-dragging');
        });

        area.addEventListener('drop', (event) => {
            event.preventDefault();
            area.classList.remove('is-dragging');

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
        ensureStyles();

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
                return `<div class="excel-preview-edit-cell" contenteditable="plaintext-only" data-field="${escapeHtml(field)}" ${attrs.join(' ')}>${escapeHtml(value)}</div>`;
            },
            static(field, value, options = {}) {
                const attrs = [];
                if (options.title) attrs.push(`title="${escapeHtml(options.title)}"`);
                const style = options.style ? ` style="${escapeHtml(options.style)}"` : '';
                return `<span data-field="${escapeHtml(field)}"${style} ${attrs.join(' ')}>${escapeHtml(value)}</span>`;
            },
            placeholder(value = '-') {
                return `<span class="excel-preview-disabled">${escapeHtml(value)}</span>`;
            },
            select(field, value, choices = []) {
                const options = choices.map((choice) => {
                    const selected = String(choice.value) === String(value) ? 'selected' : '';
                    return `<option value="${escapeHtml(choice.value)}" ${selected}>${escapeHtml(choice.label)}</option>`;
                }).join('');

                return `<select class="excel-preview-edit-select" data-field="${escapeHtml(field)}">${options}</select>`;
            },
            removeButton(title = 'Eliminar fila') {
                return `<button type="button" class="excel-preview-remove" data-excel-remove-row="true" title="${escapeHtml(title)}"><i class="fas fa-times"></i></button>`;
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
                    return cellHelpers.static(field, value ?? '', { title: column.title, style: column.textStyle });

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
                    tr.classList.add('excel-preview-row');
                    tr.style.borderBottom = '1px solid #eee';

                    (config.columns || []).forEach((column) => {
                        const td = document.createElement('td');
                        td.style.padding = column.padding || '4px 6px';
                        if (column.align) td.style.textAlign = column.align;
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
                    li.textContent = error;
                    if (typeof options.emphasize === 'function' && options.emphasize(error)) {
                        li.style.fontWeight = 'bold';
                    }
                    errorItems.appendChild(li);
                });

                errorList.style.display = errors.length ? 'block' : 'none';
            },

            clearErrors() {
                if (!errorList || !errorItems) return;
                errorList.style.display = 'none';
                errorItems.innerHTML = '';
            },

            clearHighlights(className = 'excel-preview-row-error') {
                tbody.querySelectorAll(`tr.${className}`).forEach((tr) => {
                    tr.classList.remove(className);
                });
            },

            highlightRows(predicate, options = {}) {
                const className = options.className || 'excel-preview-row-error';

                tbody.querySelectorAll('tr').forEach((tr, index) => {
                    if (!predicate(tr, index)) return;

                    tr.classList.add(className);

                    if (options.field) {
                        const fieldElement = tr.querySelector(`[data-field="${options.field}"]`);
                        if (fieldElement && options.title) {
                            fieldElement.title = options.title;
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
        normalizeHeaders,
        normalizeText,
        readExcelFile,
        resetFileInput,
    };
})();
