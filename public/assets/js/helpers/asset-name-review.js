// Revision de nombres de bienes parecidos antes de una carga masiva desde Excel.
// Detecta errores de digitacion ("Video bem", "Video ben") entre los nombres
// del archivo y contra el catalogo, y deja que el usuario decida si unificarlos.

(function () {
    let dialogOpen = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Mismo bien para el backend: sin mayusculas, tildes ni espacios repetidos.
    function nameKey(name) {
        return String(name ?? '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/\s+/g, ' ');
    }

    function allowedDistance(length) {
        if (length <= 4) return 0;
        if (length <= 8) return 1;
        if (length <= 15) return 2;
        return 3;
    }

    function levenshtein(a, b, limit) {
        let previous = Array.from({ length: b.length + 1 }, (_, index) => index);

        for (let i = 1; i <= a.length; i++) {
            const current = [i];
            let rowMin = i;

            for (let j = 1; j <= b.length; j++) {
                const cost = a[i - 1] === b[j - 1] ? 0 : 1;
                current[j] = Math.min(previous[j] + 1, current[j - 1] + 1, previous[j - 1] + cost);
                rowMin = Math.min(rowMin, current[j]);
            }

            if (rowMin > limit) return limit + 1;
            previous = current;
        }

        return previous[b.length];
    }

    function buildNode(key) {
        return {
            key,
            compact: key.replace(/[^a-z0-9]/g, ''),
            // "24000BTU" y "60000BTU" son bienes distintos aunque se escriban parecido.
            digits: (key.match(/\d+/g) || []).join('|'),
            existingName: null,
            variants: new Map(),
        };
    }

    function isSimilar(a, b) {
        if (a.digits !== b.digits) return false;

        const limit = allowedDistance(Math.max(a.compact.length, b.compact.length));
        if (Math.abs(a.compact.length - b.compact.length) > limit) return false;

        return levenshtein(a.compact, b.compact, limit) <= limit;
    }

    /**
     * Agrupa los nombres del archivo que se parecen entre si o a un bien del catalogo.
     *
     * @param {string[]} fileNames  un elemento por fila del archivo
     * @param {string[]} catalogNames
     * @returns {Array<{signature: string, nodes: object[]}>}
     */
    function findSimilarGroups(fileNames, catalogNames = []) {
        const nodes = new Map();

        fileNames.forEach((rawName) => {
            const name = String(rawName ?? '').trim();
            const key = nameKey(name);
            if (!key) return;

            if (!nodes.has(key)) nodes.set(key, buildNode(key));
            const variants = nodes.get(key).variants;
            variants.set(name, (variants.get(name) || 0) + 1);
        });

        const fileNodes = Array.from(nodes.values());
        if (!fileNodes.length) return [];

        catalogNames.forEach((rawName) => {
            const name = String(rawName ?? '').trim();
            const key = nameKey(name);
            if (!key) return;

            if (!nodes.has(key)) nodes.set(key, buildNode(key));
            nodes.get(key).existingName ??= name;
        });

        const allNodes = Array.from(nodes.values());
        const parent = new Map(allNodes.map((node) => [node, node]));
        const find = (node) => {
            while (parent.get(node) !== node) {
                parent.set(node, parent.get(parent.get(node)));
                node = parent.get(node);
            }
            return node;
        };

        // Solo se compara lo que viene en el archivo; el catalogo no se cruza consigo mismo.
        fileNodes.forEach((fileNode) => {
            allNodes.forEach((other) => {
                if (other === fileNode || !isSimilar(fileNode, other)) return;
                parent.set(find(fileNode), find(other));
            });
        });

        const clusters = new Map();
        allNodes.forEach((node) => {
            const root = find(node);
            if (!clusters.has(root)) clusters.set(root, []);
            clusters.get(root).push(node);
        });

        return Array.from(clusters.values())
            .filter((group) => group.length > 1 && group.some((node) => node.variants.size > 0))
            .map((group) => ({
                signature: group.map((node) => node.key).sort().join('\u0001'),
                nodes: group.sort((a, b) => Number(Boolean(b.existingName)) - Number(Boolean(a.existingName))),
            }));
    }

    function groupCandidates(group) {
        const candidates = [];

        group.nodes.forEach((node) => {
            const rows = Array.from(node.variants.values()).reduce((sum, count) => sum + count, 0);

            if (node.existingName) {
                candidates.push({ name: node.existingName, existing: true, rows });
                return;
            }

            const [mostUsed] = Array.from(node.variants.entries()).sort((a, b) => b[1] - a[1]);
            candidates.push({ name: mostUsed[0], existing: false, rows });
        });

        return candidates;
    }

    function defaultCandidateIndex(candidates) {
        const existingIndex = candidates.findIndex((candidate) => candidate.existing);
        if (existingIndex >= 0) return existingIndex;

        return candidates.reduce((best, candidate, index) =>
            candidate.rows > candidates[best].rows ? index : best, 0);
    }

    function renderGroup(group, groupIndex) {
        const candidates = groupCandidates(group);
        const selected = defaultCandidateIndex(candidates);
        const radioName = `nameReviewGroup${groupIndex}`;

        const options = candidates.map((candidate, index) => {
            const badges = [];
            if (candidate.existing) {
                badges.push('<span class="name-review-badge name-review-badge-existing">Ya registrado</span>');
            }
            if (candidate.rows) {
                badges.push(`<span class="name-review-badge">${candidate.rows} fila(s) en el archivo</span>`);
            }

            return `
                <label class="name-review-option">
                    <input type="radio" name="${radioName}" value="merge" data-name="${escapeHtml(candidate.name)}" ${index === selected ? 'checked' : ''}>
                    <span class="name-review-option-text">Unificar como <b>${escapeHtml(candidate.name)}</b></span>
                    <span class="name-review-badges">${badges.join('')}</span>
                </label>`;
        }).join('');

        return `
            <fieldset class="name-review-group" data-group-index="${groupIndex}">
                <legend class="name-review-group-title">Grupo ${groupIndex + 1}</legend>
                ${options}
                <label class="name-review-option name-review-option-custom">
                    <input type="radio" name="${radioName}" value="custom">
                    <span class="name-review-option-text">Unificar con otro nombre:</span>
                    <input type="text" class="name-review-custom-input" maxlength="255" placeholder="Escribe el nombre correcto">
                </label>
                <label class="name-review-option">
                    <input type="radio" name="${radioName}" value="keep">
                    <span class="name-review-option-text">Son bienes distintos, cargarlos por separado</span>
                </label>
                <p class="name-review-group-error" hidden>Escribe el nombre con el que se unificaran.</p>
            </fieldset>`;
    }

    function readDecision(fieldset) {
        const checked = fieldset.querySelector('input[type="radio"]:checked');
        const error = fieldset.querySelector('.name-review-group-error');
        error.hidden = true;

        if (!checked || checked.value === 'keep') return { action: 'keep' };

        if (checked.value === 'custom') {
            const name = fieldset.querySelector('.name-review-custom-input').value.trim();
            if (!name) {
                error.hidden = false;
                return null;
            }
            return { action: 'merge', name };
        }

        return { action: 'merge', name: checked.dataset.name };
    }

    function openDialog(groups) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'name-review-overlay';
            overlay.innerHTML = `
                <div class="name-review-dialog" role="dialog" aria-modal="true" aria-labelledby="nameReviewTitle">
                    <header class="name-review-header">
                        <p class="name-review-tag">Revision de nombres</p>
                        <h3 id="nameReviewTitle" class="name-review-title">Hay bienes con nombres parecidos</h3>
                        <p class="name-review-subtitle">
                            Pueden ser el mismo bien escrito de distintas formas. Elige con que nombre deben quedar
                            o indica que son bienes distintos.
                        </p>
                    </header>
                    <div class="name-review-body">
                        ${groups.map(renderGroup).join('')}
                    </div>
                    <footer class="name-review-footer">
                        <button type="button" class="name-review-btn name-review-btn-cancel" data-action="cancel">Cancelar envio</button>
                        <button type="button" class="name-review-btn name-review-btn-confirm" data-action="confirm">Aplicar y enviar</button>
                    </footer>
                </div>`;

            const close = (result) => {
                document.removeEventListener('keydown', onKeydown);
                overlay.remove();
                dialogOpen = false;
                resolve(result);
            };

            const onKeydown = (event) => {
                if (event.key === 'Escape') close(null);
            };

            overlay.addEventListener('focusin', (event) => {
                if (!event.target.classList.contains('name-review-custom-input')) return;
                const radio = event.target.closest('.name-review-option').querySelector('input[type="radio"]');
                radio.checked = true;
            });

            overlay.addEventListener('change', (event) => {
                if (event.target.type !== 'radio' || event.target.value !== 'custom') return;
                event.target.closest('.name-review-option').querySelector('.name-review-custom-input').focus();
            });

            overlay.addEventListener('click', (event) => {
                const action = event.target.closest('[data-action]')?.dataset.action;

                if (action === 'cancel') {
                    close(null);
                    return;
                }

                if (action !== 'confirm') return;

                const fieldsets = Array.from(overlay.querySelectorAll('.name-review-group'));
                const decisions = fieldsets.map(readDecision);
                const invalid = decisions.findIndex((decision) => decision === null);

                if (invalid >= 0) {
                    fieldsets[invalid].querySelector('.name-review-custom-input').focus();
                    return;
                }

                close(decisions);
            });

            document.addEventListener('keydown', onKeydown);
            document.body.appendChild(overlay);
            overlay.querySelector('[data-action="confirm"]').focus();
        });
    }

    async function fetchCatalogNames() {
        try {
            const response = await fetch('/api/goods/get/json', {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            return data.map((item) => item.bien).filter(Boolean);
        } catch (error) {
            // Sin catalogo igual se revisan los nombres del archivo entre si.
            console.error('No se pudo consultar el catalogo de bienes:', error);
            return [];
        }
    }

    function acceptedSignatures(tr) {
        return (tr.dataset.nameReviewKeep || '').split('\u0002').filter(Boolean);
    }

    /**
     * Revisa los nombres de la previsualizacion y aplica lo que el usuario decida.
     * Resuelve `true` si la carga puede continuar y `false` si el usuario la cancelo.
     *
     * @param {{tbody: HTMLElement, field: string}} options
     */
    async function confirmNames({ tbody, field }) {
        if (dialogOpen) return false;
        if (!tbody) return true;

        const entries = Array.from(tbody.querySelectorAll('tr'))
            .map((tr) => ({ tr, element: tr.querySelector(`[data-field="${field}"]`) }))
            .filter(({ element }) => element)
            .map((entry) => ({ ...entry, name: String(entry.element.textContent ?? '').trim() }))
            .filter(({ name }) => name);

        if (!entries.length) return true;

        dialogOpen = true;
        const catalogNames = await fetchCatalogNames();

        const groups = findSimilarGroups(entries.map(({ name }) => name), catalogNames)
            .filter((group) => {
                const keys = new Set(group.nodes.map((node) => node.key));
                const rows = entries.filter(({ name }) => keys.has(nameKey(name)));
                // Si el usuario ya dijo que son distintos, no se vuelve a preguntar.
                return !rows.every(({ tr }) => acceptedSignatures(tr).includes(group.signature));
            });

        if (!groups.length) {
            dialogOpen = false;
            return true;
        }

        const decisions = await openDialog(groups);
        if (!decisions) return false;

        decisions.forEach((decision, index) => {
            const keys = new Set(groups[index].nodes.map((node) => node.key));
            const rows = entries.filter(({ name }) => keys.has(nameKey(name)));

            rows.forEach(({ tr, element, name }) => {
                if (decision.action === 'keep') {
                    tr.dataset.nameReviewKeep = [...acceptedSignatures(tr), groups[index].signature].join('\u0002');
                    return;
                }

                if (name === decision.name) return;

                element.textContent = decision.name;
                element.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        return true;
    }

    window.AssetNameReview = {
        confirm: confirmNames,
        findSimilarGroups,
    };
})();
