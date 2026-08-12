/**
 * schedule-public.js
 *
 * Formulario público de la "Programación de inventarios".
 * Página independiente del aplicativo: no depende de los helpers internos.
 *
 * Se ocupa de tres cosas:
 *   1. la animación de inicio (igual a la del acceso);
 *   2. las evidencias fotográficas, que se suben de a una antes de enviar
 *      el formulario para que no haya límite práctico de imágenes;
 *   3. el visor a pantalla completa del comprobante.
 */

(() => {
    'use strict';

    // ─── Animación de inicio ───────────────────────────────────────

    const initSplash = () => {
        const splash = document.querySelector('[data-splash]');

        if (!splash) return;

        const box = document.querySelector('[data-splash-box]');
        const sweep = document.querySelector('[data-splash-sweep]');
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const dismiss = () => {
            if (splash.dataset.state === 'off') return;

            splash.dataset.state = 'off';
            box?.setAttribute('data-state', 'exit');
            window.setTimeout(() => splash.remove(), 800);
        };

        if (reduce) {
            dismiss();
            return;
        }

        window.setTimeout(() => sweep?.setAttribute('data-active', 'true'), 700);
        window.setTimeout(dismiss, 2600);

        // Permite saltar la animación con un toque o una tecla.
        splash.addEventListener('click', dismiss);
        document.addEventListener('keydown', dismiss, { once: true });
    };

    // ─── Coherencia de las fechas ──────────────────────────────────

    const initDateRange = () => {
        const started = document.getElementById('started_at');
        const finished = document.getElementById('finished_at');

        if (!started || !finished) return;

        // La finalización nunca puede ser anterior al inicio.
        const syncMin = () => {
            finished.min = started.value || '';

            if (finished.value && started.value && finished.value < started.value) {
                finished.value = started.value;
            }
        };

        started.addEventListener('change', syncMin);
        syncMin();
    };

    // ─── Evidencias fotográficas ───────────────────────────────────

    /** Lado máximo con el que se envía cada foto desde el navegador. */
    const MAX_SIDE = 1600;

    const formatSize = (bytes) => (bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`);

    /**
     * Reduce la foto antes de subirla.
     *
     * Las cámaras de celular producen archivos de varios megas; enviarlos
     * tal cual haría lentísimo el formulario en una conexión de campo.
     * Si el navegador no puede procesar la imagen se envía el original.
     */
    const compress = (file) => new Promise((resolve) => {
        // Sin `toBlob` no hay forma de reencodar: se envía el original.
        if (!file.type.startsWith('image/')
            || typeof HTMLCanvasElement === 'undefined'
            || typeof HTMLCanvasElement.prototype.toBlob !== 'function') {
            resolve(file);
            return;
        }

        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);

            const scale = Math.min(1, MAX_SIDE / Math.max(image.width, image.height));
            const canvas = document.createElement('canvas');

            canvas.width = Math.max(1, Math.round(image.width * scale));
            canvas.height = Math.max(1, Math.round(image.height * scale));

            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);

            canvas.toBlob((blob) => {
                if (!blob || blob.size >= file.size) {
                    resolve(file);
                    return;
                }

                const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                resolve(new File([blob], name, { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.82);
        };

        image.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(file);
        };

        image.src = url;
    });

    const initEvidence = () => {
        const form = document.querySelector('[data-schedule-form]');
        const container = document.querySelector('[data-evidence]');

        if (!form || !container) return;

        const input = container.querySelector('[data-evidence-input]');
        const addButton = container.querySelector('[data-evidence-add]');
        const list = container.querySelector('[data-evidence-list]');
        const empty = container.querySelector('[data-evidence-empty]');
        const submit = form.querySelector('[data-submit]');
        const endpoint = form.dataset.evidenceEndpoint;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Los índices nunca se reutilizan: quitar una foto no debe
        // reescribir los nombres de las que ya están subidas.
        let nextIndex = 0;
        let uploading = 0;

        const syncState = () => {
            const total = list.children.length;

            empty.hidden = total > 0;

            if (!submit) return;

            submit.disabled = uploading > 0;
            submit.innerHTML = uploading > 0
                ? '<i class="fas fa-spinner fa-spin"></i> Subiendo imágenes...'
                : '<i class="fas fa-paper-plane"></i> Enviar registro';
        };

        const buildItem = (file, previewUrl, index) => {
            const item = document.createElement('li');

            item.className = 'evidence-item';
            item.dataset.state = 'uploading';
            item.innerHTML = `
                <div class="evidence-thumb">
                    <img alt="">
                    <span class="evidence-overlay"><i class="fas fa-spinner fa-spin"></i></span>
                </div>
                <div class="evidence-body">
                    <p class="evidence-name">
                        <span class="evidence-file"></span>
                        <span class="evidence-size"></span>
                    </p>
                    <input type="text" class="evidence-caption" maxlength="250"
                           placeholder="Descripción de la imagen (opcional)" disabled>
                    <p class="evidence-status">Subiendo imagen...</p>
                </div>
                <button type="button" class="evidence-remove" data-evidence-remove
                        aria-label="Quitar imagen">
                    <i class="fas fa-xmark"></i>
                </button>
            `;

            item.querySelector('img').src = previewUrl;
            item.querySelector('.evidence-file').textContent = file.name;
            item.querySelector('.evidence-size').textContent = formatSize(file.size);
            item.dataset.index = String(index);

            return item;
        };

        const markFailed = (item, message) => {
            item.dataset.state = 'error';
            item.querySelector('.evidence-overlay').innerHTML = '<i class="fas fa-triangle-exclamation"></i>';
            item.querySelector('.evidence-status').textContent = message;
        };

        const markReady = (item, index, payload) => {
            item.dataset.state = 'ready';
            item.querySelector('.evidence-overlay').remove();
            item.querySelector('.evidence-status').textContent = 'Imagen lista.';

            const caption = item.querySelector('.evidence-caption');
            caption.disabled = false;
            caption.name = `images[${index}][description]`;

            // El token solo se envía cuando el archivo ya está en el
            // servidor: una subida fallida nunca viaja con el formulario.
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `images[${index}][token]`;
            hidden.value = payload.token;
            item.appendChild(hidden);

            const original = document.createElement('input');
            original.type = 'hidden';
            original.name = `images[${index}][original_name]`;
            original.value = payload.original_name || '';
            item.appendChild(original);

            if (payload.size) {
                item.querySelector('.evidence-size').textContent = formatSize(payload.size);
            }
        };

        const upload = async (file) => {
            const index = nextIndex++;
            const previewUrl = URL.createObjectURL(file);
            const item = buildItem(file, previewUrl, index);

            list.appendChild(item);
            uploading += 1;
            syncState();

            try {
                const payload = await compress(file);
                const body = new FormData();
                body.append('file', payload, payload.name);

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        Accept: 'application/json',
                    },
                    body,
                });

                const result = await response.json().catch(() => ({}));

                if (!response.ok || !result.success) {
                    markFailed(item, result.message
                        || result.errors?.file?.[0]
                        || 'No se pudo subir esta imagen. Quítala e inténtalo otra vez.');
                    return;
                }

                markReady(item, index, result);
            } catch (error) {
                markFailed(item, 'Se perdió la conexión al subir esta imagen.');
            } finally {
                uploading -= 1;
                syncState();
            }
        };

        addButton.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            Array.from(input.files || []).forEach(upload);

            // Permite volver a elegir el mismo archivo si hizo falta.
            input.value = '';
        });

        list.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-evidence-remove]');

            if (!remove) return;

            const item = remove.closest('.evidence-item');
            const preview = item?.querySelector('img')?.src;

            if (preview?.startsWith('blob:')) {
                URL.revokeObjectURL(preview);
            }

            item?.remove();
            syncState();
        });

        form.addEventListener('submit', (event) => {
            if (uploading === 0) return;

            event.preventDefault();
        });

        syncState();
    };

    // ─── Visor de evidencias del comprobante ───────────────────────

    const initViewer = () => {
        const viewer = document.querySelector('[data-viewer]');

        if (!viewer) return;

        const image = viewer.querySelector('[data-viewer-image]');
        const caption = viewer.querySelector('[data-viewer-caption]');

        const close = () => {
            viewer.hidden = true;
            image.src = '';
            document.body.classList.remove('viewer-open');
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-viewer-open]');

            if (trigger) {
                image.src = trigger.dataset.src;
                image.alt = trigger.dataset.caption || 'Evidencia fotográfica';
                caption.textContent = trigger.dataset.caption || '';
                caption.hidden = !trigger.dataset.caption;
                viewer.hidden = false;
                document.body.classList.add('viewer-open');
                return;
            }

            if (event.target.closest('[data-viewer-close]') || event.target === viewer) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !viewer.hidden) {
                close();
            }
        });
    };

    // ─── Arranque ──────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => {
        initSplash();
        initDateRange();
        initEvidence();
        initViewer();
    });
})();
