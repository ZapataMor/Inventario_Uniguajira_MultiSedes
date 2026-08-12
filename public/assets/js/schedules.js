/**
 * schedules.js
 *
 * Módulo "Programación de inventarios".
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
        inventoryIds: (card.dataset.inventoryIds || '')
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean),
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
    };

    // ─── Selector múltiple de ubicaciones ──────────────────────────

    /**
     * Refresca el contador de ubicaciones marcadas dentro de un formulario.
     */
    const updateLocationsCount = (form) => {
        const counter = form.querySelector('[data-locations-count]');

        if (!counter) return;

        const total = form.querySelectorAll('input[name="inventory_ids[]"]:checked').length;

        counter.textContent = total === 0
            ? 'Ninguna ubicación seleccionada.'
            : `${total} ${total === 1 ? 'ubicación seleccionada' : 'ubicaciones seleccionadas'}.`;
    };

    /**
     * Filtra la lista de ubicaciones por el texto escrito en el buscador.
     */
    const filterLocations = (form) => {
        const term = (form.querySelector('[data-locations-search]')?.value || '').toLowerCase().trim();

        form.querySelectorAll('[data-location-search]').forEach((option) => {
            const match = term === '' || (option.dataset.locationSearch || '').includes(term);

            option.style.display = match ? '' : 'none';
        });
    };

    /**
     * Deja marcadas solo las ubicaciones indicadas.
     */
    const setSelectedLocations = (form, ids) => {
        const selected = new Set((ids || []).map(String));

        form.querySelectorAll('input[name="inventory_ids[]"]').forEach((checkbox) => {
            checkbox.checked = selected.has(checkbox.value);
        });

        const search = form.querySelector('[data-locations-search]');
        if (search) search.value = '';

        filterLocations(form);
        updateLocationsCount(form);
    };

    // ─── Modal de creación / edición ───────────────────────────────

    window.btnCrearProgramacion = () => {
        const form = document.getElementById('formCrearProgramacion');

        if (!form) return;

        form.reset();
        setSelectedLocations(form, []);
        mostrarModal('#modalCrearProgramacion');
    };

    const openEditModal = (data) => {
        const form = document.getElementById('formEditarProgramacion');

        if (!form) return;

        document.getElementById('editarProgramacionId').value = data.id;
        document.getElementById('editarProgramacionTitle').value = data.title || '';
        setSelectedLocations(form, data.inventoryIds);

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
                    <h3 class="sched-entry-name">${escapeHtml(entry.work_name)}</h3>
                    <span class="sched-entry-duration">${escapeHtml(entry.duration)}</span>
                </div>
                ${entry.receipt_code ? `<p class="sched-entry-folio">Comprobante ${escapeHtml(entry.receipt_code)}</p>` : ''}
                ${entry.description ? `<p class="sched-entry-description">${escapeHtml(entry.description)}</p>` : ''}
                <ul class="sched-entry-meta">
                    <li><i class="fas fa-user"></i> ${escapeHtml(entry.responsible_name)}</li>
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
        receipt?.classList.add('hidden');

        mostrarModal('#modalProgramacionRegistros');

        fetch(`/api/schedules/${data.id}/entries`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.json())
            .then((payload) => {
                renderEntries(payload.entries || []);

                // El comprobante solo se ofrece si el formulario ya fue diligenciado.
                if (receipt && payload.receipt_url) {
                    receipt.href = payload.receipt_url;
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

                form.reset();
                setSelectedLocations(form, []);
                refreshSchedules();
            })
            .catch(() => showToast({ success: false, message: 'No se pudo guardar la programación.' }))
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
                    case 'edit': openEditModal(data); break;
                    case 'delete': deleteSchedule(data); break;
                }

                return;
            }

            // Clic en el cuerpo de una tarjeta ya diligenciada: abre el
            // detalle, que es donde se consultan las evidencias.
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

                if (form) filterLocations(form);
            }
        });

        document.addEventListener('change', (event) => {
            if (!event.target?.matches('input[name="inventory_ids[]"]')) return;

            const form = event.target.closest('form');

            if (form) updateLocationsCount(form);
        });

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('#formCrearProgramacion, #formEditarProgramacion');

            if (!form) return;

            event.preventDefault();
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

        if (document.getElementById('schedulesGrid')) {
            renderCardQrs();
            applyFilters();
        }
    });
})();
