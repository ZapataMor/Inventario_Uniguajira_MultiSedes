/**
 * schedules.js
 *
 * Módulo "Programación de mantenimientos".
 * El QR y el enlace se renderizan dentro de cada tarjeta, así que la vista
 * queda lista para escanear o copiar sin abrir ventanas intermedias.
 *
 * Cada QR se diligencia una sola vez: cuando la programación ya tiene su
 * labor documentada, el servidor deja de imprimir el bloque del QR y en su
 * lugar la tarjeta muestra lo registrado.
 */

(() => {
    // ─── Utilidades ────────────────────────────────────────────────

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const readCard = (card) => ({
        id: card.dataset.id,
        code: card.dataset.code,
        title: card.dataset.title,
        isOpen: card.dataset.open === '1',
        isCompleted: card.dataset.completed === '1',
        url: card.dataset.url,
        // Solo en el portal: sede a la que pertenece la tarjeta.
        tenant: card.dataset.tenant || '',
        payload: (() => {
            try {
                return JSON.parse(card.dataset.payload || '{}');
            } catch (error) {
                return {};
            }
        })(),
        element: card,
    });

    /**
     * Recarga la vista sin perder la navegación SPA.
     */
    const refreshSchedules = () => {
        if (typeof loadContent === 'function') {
            loadContent(window.location.pathname + window.location.search, {
                updateHistory: false,
                onSuccess: () => initSchedulesModule(),
            });
            return;
        }

        window.location.reload();
    };

    // ─── Códigos QR dentro de las tarjetas ─────────────────────────

    const renderCardQrs = () => {
        document.querySelectorAll('[data-qr-target]').forEach((container) => {
            if (container.dataset.qrRendered === '1') return;

            const url = container.dataset.url;

            if (!url) return;

            container.innerHTML = '';

            if (typeof QRCode === 'undefined') {
                container.innerHTML = '<span class="sched-qr-loading">No se pudo cargar el generador de QR.</span>';
                return;
            }

            try {
                new QRCode(container, {
                    text: url,
                    width: 150,
                    height: 150,
                    correctLevel: QRCode.CorrectLevel.M,
                });
                container.dataset.qrRendered = '1';
            } catch (error) {
                console.error('No se pudo generar el QR:', error);
                container.innerHTML = '<span class="sched-qr-loading">No se pudo generar el QR.</span>';
            }
        });
    };

    /**
     * Devuelve el QR de una tarjeta como data URL (canvas o img según el navegador).
     */
    const getCardQrDataUrl = (card) => {
        const container = card.querySelector('[data-qr-target]');
        const canvas = container?.querySelector('canvas');

        if (canvas) {
            return canvas.toDataURL('image/png');
        }

        return container?.querySelector('img')?.src || null;
    };

    // ─── Búsqueda ──────────────────────────────────────────────────

    const applyFilters = () => {
        const term = (document.getElementById('searchSchedule')?.value || '').toLowerCase().trim();
        const cards = document.querySelectorAll('[data-schedule-card]');
        let visible = 0;

        cards.forEach((card) => {
            const show = term === '' || (card.dataset.search || '').includes(term);

            card.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });

        const noResults = document.querySelector('[data-schedule-no-results]');

        if (noResults) {
            noResults.classList.toggle('hidden', visible > 0 || cards.length === 0);
        }

        syncSedeDropdowns(term !== '');
    };

    /**
     * Portal: cada sede es un desplegable. Con búsqueda activa se abren
     * las sedes con coincidencias; sin búsqueda quedan todas cerradas.
     */
    const syncSedeDropdowns = (hasFilter) => {
        document.querySelectorAll('[data-schedule-sede-dropdown]').forEach((dropdown) => {
            const cards = Array.from(dropdown.querySelectorAll('[data-schedule-card]'));
            const visibleCards = cards.filter((card) => card.style.display !== 'none').length;
            const count = dropdown.querySelector('[data-visible-count]');
            const empty = dropdown.querySelector('[data-sede-empty]');

            if (count) count.textContent = String(visibleCards);
            if (empty) empty.classList.toggle('hidden', visibleCards > 0);

            const controller = typeof createSedeDropdownController === 'function'
                ? createSedeDropdownController(dropdown, '.inventory-sede-body')
                : { setOpen: (shouldOpen) => { dropdown.open = shouldOpen; } };

            controller.setOpen(hasFilter && visibleCards > 0, true);
        });
    };

    // ─── Localización: bloque → salones ────────────────────────────

    /**
     * Refresca el contador de salones marcados dentro de un formulario.
     */
    const updateLocationsCount = (form) => {
        const counter = form.querySelector('[data-locations-count]');

        if (!counter) return;

        const total = form.querySelectorAll('input[name="inventory_ids[]"]:checked').length;

        counter.textContent = total === 0
            ? 'Ningún salón seleccionado.'
            : `${total} ${total === 1 ? 'salón seleccionado' : 'salones seleccionados'}.`;
    };

    /**
     * Muestra solo los salones del bloque elegido que coinciden con la
     * búsqueda. Los de otros bloques se desmarcan: la localización es
     * siempre un bloque y sus salones, nunca una mezcla.
     */
    const syncRooms = (form) => {
        const blockId = form.querySelector('[data-block-select]')?.value || '';
        const field = form.querySelector('[data-rooms-field]');
        const term = (form.querySelector('[data-locations-search]')?.value || '').toLowerCase().trim();

        if (field) field.hidden = blockId === '';

        form.querySelectorAll('[data-group-id]').forEach((option) => {
            const inBlock = option.dataset.groupId === blockId;
            const match = term === '' || (option.dataset.locationSearch || '').includes(term);

            if (!inBlock) {
                option.querySelector('input').checked = false;
            }

            option.hidden = !(inBlock && match);
        });

        updateLocationsCount(form);
    };

    // ─── Tipo de servicio: casilla "Otros" ─────────────────────────

    const syncServiceOther = (form) => {
        const toggle = form.querySelector('[data-service-other-toggle]');
        const field = form.querySelector('[data-service-other-field]');
        const input = field?.querySelector('input');

        if (!toggle || !field || !input) return;

        field.hidden = !toggle.checked;
        input.disabled = !toggle.checked;
        input.required = toggle.checked;
    };

    // ─── Etapas del formulario ─────────────────────────────────────

    const STAGE_COUNT = 3;

    const currentStage = (form) => Number(form.dataset.stage || 1);

    const showStageError = (form, stage, message) => {
        const error = form.querySelector(`[data-stage="${stage}"] [data-stage-error]`);

        if (!error) return;

        error.textContent = message || '';
        error.hidden = !message;
    };

    const goToStage = (form, stage) => {
        form.dataset.stage = String(stage);

        form.querySelectorAll('[data-stage]').forEach((section) => {
            section.hidden = Number(section.dataset.stage) !== stage;
        });

        form.querySelectorAll('[data-stage-step]').forEach((step) => {
            const number = Number(step.dataset.stageStep);

            step.classList.toggle('is-active', number === stage);
            step.classList.toggle('is-done', number < stage);
        });

        form.querySelector('[data-stage-prev]').hidden = stage === 1;
        form.querySelector('[data-stage-next]').hidden = stage === STAGE_COUNT;
        form.querySelector('[data-stage-submit]').hidden = stage !== STAGE_COUNT;

        showStageError(form, stage, '');

        // El modal hace scroll propio: al cambiar de etapa se vuelve arriba.
        form.closest('.modal-content')?.scrollTo({ top: 0 });

        const firstField = form.querySelector(`[data-stage="${stage}"] input:not([type="hidden"]):not([disabled])`);
        window.setTimeout(() => firstField?.focus(), 60);
    };

    /**
     * Valida los campos visibles de una etapa. El navegador no puede
     * señalar campos de etapas ocultas, por eso se valida al avanzar.
     */
    const validateStage = (form, stage) => {
        const section = form.querySelector(`[data-stage="${stage}"]`);

        if (!section) return true;

        const fields = section.querySelectorAll('input, select, textarea');

        for (const field of fields) {
            if (field.disabled || field.type === 'hidden') continue;
            if (field.closest('[hidden]')) continue;

            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }

        if (stage === 2) {
            const anyService = section.querySelector('input[name="service_types[]"]:checked, [data-service-other-toggle]:checked');

            if (!anyService) {
                showStageError(form, stage, 'Marca al menos un tipo de servicio.');
                return false;
            }
        }

        if (stage === 3 && form.querySelector('[data-block-select]')) {
            if (!section.querySelector('input[name="inventory_ids[]"]:checked')) {
                showStageError(form, stage, 'Selecciona al menos un salón o sala del bloque.');
                return false;
            }
        }

        showStageError(form, stage, '');

        return true;
    };

    const todayValue = () => {
        const now = new Date();
        const pad = (value) => String(value).padStart(2, '0');

        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    };

    /**
     * Deja el formulario en blanco y en la primera etapa.
     */
    const resetWizard = (form) => {
        form.reset();

        form.querySelectorAll('[data-default-today]').forEach((input) => {
            input.value = todayValue();
        });

        const search = form.querySelector('[data-locations-search]');
        if (search) search.value = '';

        syncServiceOther(form);
        syncRooms(form);
        goToStage(form, 1);
    };

    /**
     * Precarga el formulario de edición con los datos de la tarjeta.
     */
    const fillWizard = (form, payload) => {
        const setValue = (name, value) => {
            const field = form.elements.namedItem(name);

            if (field && 'value' in field) field.value = value ?? '';
        };

        const checkValues = (name, values) => {
            const selected = new Set((values || []).map(String));

            form.querySelectorAll(`input[name="${name}"]`).forEach((input) => {
                input.checked = selected.has(input.value);
            });
        };

        setValue('id', payload.id);
        setValue('requester_name', payload.requester_name);
        setValue('requester_position', payload.requester_position);
        setValue('requester_dependency', payload.requester_dependency);
        setValue('filing_number', payload.filing_number);
        setValue('requested_at', payload.requested_at || todayValue());

        checkValues('service_types[]', payload.service_types);

        const otherToggle = form.querySelector('[data-service-other-toggle]');
        if (otherToggle) otherToggle.checked = Boolean(payload.service_other);
        setValue('service_other', payload.service_other);
        syncServiceOther(form);

        checkValues('activity_type', payload.activity_type ? [payload.activity_type] : []);
        checkValues('maintenance_type', payload.maintenance_type ? [payload.maintenance_type] : []);

        setValue('group_id', payload.group_id);
        syncRooms(form);
        checkValues('inventory_ids[]', payload.inventory_ids);
        updateLocationsCount(form);
    };

    // ─── Modal de creación / edición ───────────────────────────────

    window.btnCrearProgramacion = () => {
        const form = document.getElementById('formCrearProgramacion');

        if (!form) return;

        resetWizard(form);
        mostrarModal('#modalCrearProgramacion');
    };

    const openEditModal = (data) => {
        const form = document.getElementById('formEditarProgramacion');

        if (!form) return;

        resetWizard(form);
        fillWizard(form, data.payload);

        mostrarModal('#modalEditarProgramacion');
    };

    // ─── Compartir: copiar, descargar, imprimir ────────────────────

    const copyLink = async (card) => {
        const input = card.querySelector('.sched-link-row input');

        if (!input?.value) return;

        try {
            await navigator.clipboard.writeText(input.value);
            showToast({ success: true, message: 'Enlace copiado al portapapeles.' });
        } catch (error) {
            // Respaldo para navegadores sin acceso al portapapeles.
            input.select();
            document.execCommand('copy');
            showToast({ success: true, message: 'Enlace copiado.' });
        }
    };

    const downloadQr = (data) => {
        const dataUrl = getCardQrDataUrl(data.element);

        if (!dataUrl) {
            showToast({ success: false, message: 'No se pudo preparar la imagen del QR.' });
            return;
        }

        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = `qr-${data.code || 'programacion'}.png`;
        document.body.appendChild(link);
        link.click();
        link.remove();
    };

    const printQr = (data) => {
        const dataUrl = getCardQrDataUrl(data.element);

        if (!dataUrl) {
            showToast({ success: false, message: 'No se pudo preparar la impresión.' });
            return;
        }

        const win = window.open('', '_blank');

        if (!win) {
            showToast({ success: false, message: 'El navegador bloqueó la ventana de impresión.' });
            return;
        }

        win.document.write(`
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>QR - ${escapeHtml(data.title)}</title>
                <style>
                    body { font-family: "Segoe UI", Arial, sans-serif; text-align: center; padding: 40px 24px; color: #1e293b; }
                    h1 { font-size: 20px; margin: 0 0 6px; }
                    p { margin: 4px 0; color: #64748b; font-size: 14px; }
                    img { margin: 24px auto; display: block; }
                    .link { font-size: 12px; word-break: break-all; color: #334155; }
                </style>
            </head>
            <body>
                <h1>${escapeHtml(data.title)}</h1>
                <img src="${dataUrl}" alt="Código QR" width="260" height="260">
                <p>Escanea el código para registrar la labor realizada.</p>
                <p class="link">${escapeHtml(data.url || '')}</p>
            </body>
            </html>
        `);
        win.document.close();
        win.focus();
        win.print();
    };

    const toggleOpen = (data) => {
        fetch('/api/schedules/toggle-open', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ id: data.id }),
        })
            .then((response) => response.json())
            .then((response) => {
                showToast(response);

                if (response.success) {
                    refreshSchedules();
                }
            })
            .catch(() => showToast({ success: false, message: 'No se pudo cambiar el estado del formulario.' }));
    };

    // ─── Modal de labores documentadas ─────────────────────────────

    /**
     * Galería de evidencias de una labor. Cada miniatura abre el visor.
     */
    const renderEntryImages = (images) => {
        if (!images || !images.length) {
            return '<p class="sched-entry-no-shots">Esta labor no tiene evidencias fotográficas.</p>';
        }

        const cards = images.map((image, index) => `
            <li class="sched-shot">
                <button type="button" class="sched-shot-open"
                        data-sched-viewer-open
                        data-src="${escapeHtml(image.url)}"
                        data-caption="${escapeHtml(image.description || '')}">
                    <img src="${escapeHtml(image.url)}"
                         alt="Evidencia ${index + 1}" loading="lazy">
                    <span class="sched-shot-zoom"><i class="fas fa-magnifying-glass-plus"></i></span>
                </button>
                <p class="sched-shot-caption${image.description ? '' : ' sched-shot-caption-empty'}">
                    ${escapeHtml(image.description || 'Sin descripción')}
                </p>
            </li>
        `).join('');

        return `
            <p class="sched-entry-shots-label">
                <i class="fas fa-images"></i>
                Evidencias fotográficas (${images.length})
            </p>
            <ul class="sched-shot-grid">${cards}</ul>
        `;
    };

    const renderEntries = (entries) => {
        const body = document.querySelector('[data-entries-body]');

        if (!body) return;

        if (!entries.length) {
            body.innerHTML = '<p class="sched-entries-empty">Aún no hay labores documentadas para esta programación.</p>';
            return;
        }

        body.innerHTML = entries.map((entry) => `
            <article class="sched-entry">
                <div class="sched-entry-head">
                    <h3 class="sched-entry-name">Acción realizada</h3>
                    <span class="sched-entry-duration">${escapeHtml(entry.duration)}</span>
                </div>
                ${entry.action ? `<p class="sched-entry-description">${escapeHtml(entry.action)}</p>` : ''}
                ${entry.equipment ? `<p class="sched-entry-extra"><strong>Equipo:</strong> ${escapeHtml(entry.equipment)}</p>` : ''}
                ${entry.materials ? `<p class="sched-entry-extra"><strong>Materiales:</strong> ${escapeHtml(entry.materials)}</p>` : ''}
                <ul class="sched-entry-meta">
                    <li><i class="fas fa-user"></i> Realizada por: ${escapeHtml(entry.performed_by || '—')}</li>
                    <li><i class="fas fa-play"></i> Inicio: ${escapeHtml(entry.started_at)}</li>
                    <li><i class="fas fa-flag-checkered"></i> Fin: ${escapeHtml(entry.finished_at)}</li>
                    <li><i class="fas fa-clock"></i> Registrado: ${escapeHtml(entry.registered_at)}</li>
                </ul>
                <div class="sched-entry-shots">${renderEntryImages(entry.images)}</div>
            </article>
        `).join('');
    };

    const openEntriesModal = (data) => {
        const body = document.querySelector('[data-entries-body]');
        const title = document.querySelector('[data-entries-title]');
        const receipt = document.querySelector('[data-entries-receipt]');

        if (title) title.textContent = data.title || '';
        if (body) body.innerHTML = '<p class="sched-entries-loading">Cargando registros...</p>';

        if (receipt) {
            receipt.classList.add('hidden');
            receipt.dataset.scheduleId = data.id;
            receipt.dataset.scheduleTitle = data.title || '';
        }

        mostrarModal('#modalProgramacionRegistros');

        // Desde el portal la programación se lee en la base de su sede.
        const query = data.tenant
            ? `?${new URLSearchParams({ tenant: data.tenant, portal: '1' })}`
            : '';

        fetch(`/api/schedules/${data.id}/entries${query}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.json())
            .then((payload) => {
                renderEntries(payload.entries || []);

                // El formato solo se ofrece si el formulario ya fue diligenciado.
                if (receipt && payload.can_download) {
                    receipt.classList.remove('hidden');
                }
            })
            .catch(() => {
                if (body) {
                    body.innerHTML = '<p class="sched-entries-empty">No se pudieron cargar los registros.</p>';
                }
            });
    };

    // ─── Visor de evidencias ───────────────────────────────────────

    const closeViewer = () => {
        const viewer = document.querySelector('[data-sched-viewer]');

        if (!viewer || viewer.hidden) return;

        viewer.hidden = true;
        viewer.querySelector('[data-sched-viewer-image]').src = '';
        document.body.classList.remove('sched-viewer-open');
    };

    const openViewer = (trigger) => {
        const viewer = document.querySelector('[data-sched-viewer]');

        if (!viewer) return;

        const image = viewer.querySelector('[data-sched-viewer-image]');
        const caption = viewer.querySelector('[data-sched-viewer-caption]');
        const text = trigger.dataset.caption || '';

        image.src = trigger.dataset.src;
        image.alt = text || 'Evidencia fotográfica';
        caption.textContent = text;
        caption.hidden = text === '';
        viewer.hidden = false;
        document.body.classList.add('sched-viewer-open');
    };

    // ─── Formato RA-F-33: vista previa y descarga firmada ──────────

    const PDFJS_VERSION = '3.11.174';
    const PDFJS_BASE = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}`;

    let pdfjsLoader = null;

    /**
     * pdf.js se carga solo la primera vez que se abre el modal. Se usa en
     * lugar de un <iframe> porque los navegadores de celular no muestran
     * PDFs incrustados.
     */
    const loadPdfJs = () => {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);

        if (!pdfjsLoader) {
            pdfjsLoader = new Promise((resolve, reject) => {
                const script = document.createElement('script');

                script.src = `${PDFJS_BASE}/pdf.min.js`;
                script.onload = () => {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = `${PDFJS_BASE}/pdf.worker.min.js`;
                    resolve(window.pdfjsLib);
                };
                script.onerror = () => {
                    pdfjsLoader = null;
                    reject(new Error('No se pudo cargar el visor de PDF.'));
                };

                document.head.appendChild(script);
            });
        }

        return pdfjsLoader;
    };

    const signModal = () => document.getElementById('modalFirmarFormato');

    const renderPreview = async (scheduleId) => {
        const modal = signModal();
        const container = modal.querySelector('[data-sign-preview]');
        const status = modal.querySelector('[data-sign-preview-status]');
        const previewUrl = `/api/schedules/${scheduleId}/receipt/preview`;

        container.querySelectorAll('canvas').forEach((canvas) => canvas.remove());
        status.hidden = false;
        status.textContent = 'Cargando vista previa...';

        try {
            const pdfjs = await loadPdfJs();
            const pdf = await pdfjs.getDocument({ url: previewUrl, withCredentials: true }).promise;
            const page = await pdf.getPage(1);

            // El modal pudo cerrarse o cambiar de programación mientras cargaba.
            if (modal.dataset.scheduleId !== String(scheduleId)) return;

            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const width = Math.max(container.clientWidth, 280);
            const base = page.getViewport({ scale: 1 });
            const viewport = page.getViewport({ scale: (width / base.width) * ratio });
            const canvas = document.createElement('canvas');

            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.className = 'sched-sign-page';

            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

            status.hidden = true;
            container.appendChild(canvas);
        } catch (error) {
            console.error('Vista previa del formato:', error);
            status.innerHTML = `No se pudo mostrar la vista previa aquí.
                <a href="${previewUrl}" target="_blank" rel="noopener">Abrir en otra pestaña</a>.`;
        }
    };

    /**
     * Firma dibujada, o la guardada si se marcó la casilla.
     */
    const syncSignState = () => {
        const modal = signModal();

        if (!modal) return;

        const useSaved = modal.querySelector('[data-sign-use-saved]')?.checked || false;
        const pad = modal.signaturePad;

        modal.querySelector('[data-sign-draw]').hidden = useSaved;
        modal.querySelector('[data-sign-saved-preview]').hidden = !useSaved;
        modal.querySelector('[data-sign-placeholder]').hidden = Boolean(pad && !pad.isEmpty());
        modal.querySelector('[data-sign-submit]').disabled = !useSaved && (!pad || pad.isEmpty());
        modal.querySelector('[data-sign-error]').hidden = true;
    };

    const openReceiptModal = (scheduleId, title) => {
        const modal = signModal();

        if (!modal || !scheduleId) return;

        modal.dataset.scheduleId = String(scheduleId);
        modal.querySelector('[data-sign-title]').textContent = title || '';

        const useSaved = modal.querySelector('[data-sign-use-saved]');
        if (useSaved) useSaved.checked = false;
        modal.querySelector('[data-sign-save]').checked = false;

        mostrarModal('#modalFirmarFormato');

        // El lienzo se crea al abrir: dentro de un modal oculto no tiene tamaño.
        if (!modal.signaturePad && typeof window.SignaturePad !== 'undefined') {
            modal.signaturePad = new window.SignaturePad(modal.querySelector('[data-sign-canvas]'), {
                onChange: syncSignState,
            });
        }

        modal.signaturePad?.clear();
        syncSignState();
        renderPreview(scheduleId);
    };

    const fileNameFrom = (response) => {
        const header = response.headers.get('Content-Disposition') || '';
        const match = header.match(/filename="?([^";]+)"?/i);

        return match ? match[1] : 'RA-F-33-solicitud-de-servicio.pdf';
    };

    const submitSignedReceipt = async () => {
        const modal = signModal();
        const scheduleId = modal.dataset.scheduleId;
        const submit = modal.querySelector('[data-sign-submit]');
        const error = modal.querySelector('[data-sign-error]');
        const useSaved = modal.querySelector('[data-sign-use-saved]')?.checked || false;
        const saveSignature = modal.querySelector('[data-sign-save]').checked;
        const pad = modal.signaturePad;

        if (!useSaved && (!pad || pad.isEmpty())) {
            error.textContent = 'Firma en el recuadro para descargar el formato.';
            error.hidden = false;
            return;
        }

        const body = new FormData();
        const drawn = useSaved ? '' : pad.toDataURL();

        body.append('use_saved', useSaved ? '1' : '0');

        if (!useSaved) {
            body.append('signature', drawn);
            body.append('save_signature', saveSignature ? '1' : '0');
        }

        const originalText = submit.innerHTML;
        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparando PDF...';

        try {
            const response = await fetch(`/api/schedules/${scheduleId}/receipt`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    Accept: 'application/pdf, application/json',
                },
                body,
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                const message = payload.errors
                    ? Object.values(payload.errors).flat()[0]
                    : (payload.message || 'No se pudo generar el formato.');

                error.textContent = message;
                error.hidden = false;
                return;
            }

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = fileNameFrom(response);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 10000);

            // La firma recién guardada queda disponible sin recargar la página.
            if (!useSaved && saveSignature) {
                modal.querySelector('[data-sign-saved-image]').src = drawn;
                modal.querySelector('[data-sign-saved-block]').hidden = false;
            }

            showToast({ success: true, message: 'Formato firmado y descargado.' });
            ocultarModal('#modalFirmarFormato');
        } catch (exception) {
            error.textContent = 'Se perdió la conexión. Inténtalo de nuevo.';
            error.hidden = false;
        } finally {
            submit.innerHTML = originalText;
            syncSignState();
        }
    };

    const forgetSavedSignature = () => {
        const modal = signModal();

        fetch('/api/schedules/signature', {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
        })
            .then((response) => response.json())
            .then((payload) => {
                showToast(payload);

                if (!payload.success) return;

                const useSaved = modal.querySelector('[data-sign-use-saved]');
                if (useSaved) useSaved.checked = false;
                modal.querySelector('[data-sign-saved-block]').hidden = true;
                syncSignState();
            })
            .catch(() => showToast({ success: false, message: 'No se pudo eliminar la firma guardada.' }));
    };

    // ─── Eliminación ───────────────────────────────────────────────

    const deleteSchedule = (data) => {
        eliminarRegistro({
            url: `/api/schedules/delete/${data.id}`,
            confirmTitle: '¿Eliminar la programación?',
            confirmText: 'También se eliminarán las labores documentadas asociadas.',
            onSuccess: (response) => {
                showToast(response);
                refreshSchedules();
            },
        });
    };

    // ─── Envío de los formularios ──────────────────────────────────

    /**
     * Los formularios se manejan de forma delegada para que el módulo
     * siga funcionando aunque la vista se recargue por navegación AJAX.
     */
    const submitScheduleForm = (form) => {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.innerHTML : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        }

        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: new FormData(form),
        })
            .then((response) => response.json())
            .then((data) => {
                showToast(data);

                if (!data.success) return;

                const modal = form.closest('.modal');
                if (modal?.id) {
                    modal.dataset.modalSaved = 'true';
                    ocultarModal(`#${modal.id}`);
                }

                resetWizard(form);
                refreshSchedules();
            })
            .catch(() => showToast({ success: false, message: 'No se pudo guardar el mantenimiento.' }))
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            });
    };

    // ─── Listeners delegados (se registran una sola vez) ───────────

    const bindGlobalListeners = () => {
        if (window.schedulesListenersBound) return;

        window.schedulesListenersBound = true;

        document.addEventListener('click', (event) => {
            const viewerTrigger = event.target.closest('[data-sched-viewer-open]');

            if (viewerTrigger) {
                openViewer(viewerTrigger);
                return;
            }

            const viewer = document.querySelector('[data-sched-viewer]');

            if (viewer && !viewer.hidden
                && (event.target === viewer || event.target.closest('[data-sched-viewer-close]'))) {
                closeViewer();
                return;
            }

            const actionButton = event.target.closest('[data-schedule-card] [data-action]');

            if (actionButton) {
                const data = readCard(actionButton.closest('[data-schedule-card]'));

                switch (actionButton.dataset.action) {
                    case 'copy': copyLink(data.element); break;
                    case 'download': downloadQr(data); break;
                    case 'print': printQr(data); break;
                    case 'toggle': toggleOpen(data); break;
                    case 'entries': openEntriesModal(data); break;
                    case 'receipt': openReceiptModal(data.id, data.title); break;
                    case 'edit': openEditModal(data); break;
                    case 'delete': deleteSchedule(data); break;
                }

                return;
            }

            // Clic en el cuerpo de una tarjeta ya diligenciada: abre el
            // detalle, que es donde se consultan las evidencias.
            const entriesReceipt = event.target.closest('[data-entries-receipt]');

            if (entriesReceipt) {
                ocultarModal('#modalProgramacionRegistros');
                openReceiptModal(entriesReceipt.dataset.scheduleId, entriesReceipt.dataset.scheduleTitle);
                return;
            }

            if (event.target.closest('[data-sign-clear]')) {
                signModal()?.signaturePad?.clear();
                return;
            }

            if (event.target.closest('[data-sign-forget]')) {
                forgetSavedSignature();
                return;
            }

            const wizard = event.target.closest('[data-schedule-wizard]');

            if (wizard && event.target.closest('[data-stage-next]')) {
                const stage = currentStage(wizard);

                if (validateStage(wizard, stage)) goToStage(wizard, Math.min(stage + 1, STAGE_COUNT));
                return;
            }

            if (wizard && event.target.closest('[data-stage-prev]')) {
                goToStage(wizard, Math.max(currentStage(wizard) - 1, 1));
                return;
            }

            const card = event.target.closest('.sched-card-clickable');

            if (card && !event.target.closest('button, a, input, textarea, select, label')) {
                openEntriesModal(readCard(card));
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeViewer();
        });

        document.addEventListener('input', (event) => {
            if (event.target?.id === 'searchSchedule') {
                applyFilters();
                return;
            }

            if (event.target?.matches('[data-locations-search]')) {
                const form = event.target.closest('form');

                if (form) syncRooms(form);
            }
        });

        document.addEventListener('change', (event) => {
            if (event.target?.matches('[data-sign-use-saved]')) {
                syncSignState();
                return;
            }

            const form = event.target?.closest('[data-schedule-wizard]');

            if (!form) return;

            if (event.target.matches('[data-block-select]')) {
                const search = form.querySelector('[data-locations-search]');
                if (search) search.value = '';

                syncRooms(form);
                showStageError(form, 3, '');
                return;
            }

            if (event.target.matches('[data-service-other-toggle]')) {
                syncServiceOther(form);
            }

            if (event.target.matches('input[name="inventory_ids[]"]')) {
                updateLocationsCount(form);
            }

            if (event.target.matches('input[type="checkbox"]')) {
                showStageError(form, currentStage(form), '');
            }
        });

        document.addEventListener('submit', (event) => {
            if (event.target.closest('[data-sign-form]')) {
                event.preventDefault();
                submitSignedReceipt();
                return;
            }

            const form = event.target.closest('#formCrearProgramacion, #formEditarProgramacion');

            if (!form) return;

            event.preventDefault();

            // Enter en una etapa intermedia avanza en vez de enviar.
            const stage = currentStage(form);

            if (!validateStage(form, stage)) return;

            if (stage < STAGE_COUNT) {
                goToStage(form, stage + 1);
                return;
            }

            submitScheduleForm(form);
        });
    };

    // ─── Inicialización ────────────────────────────────────────────

    window.initSchedulesModule = () => {
        bindGlobalListeners();
        renderCardQrs();
        applyFilters();
    };

    document.addEventListener('DOMContentLoaded', () => {
        // Los listeners son delegados, así que se registran siempre:
        // el módulo queda operativo aunque se llegue por navegación AJAX.
        bindGlobalListeners();

        if (document.getElementById('schedulesGrid') || document.getElementById('schedulesPortal')) {
            renderCardQrs();
            applyFilters();
        }
    });
})();
