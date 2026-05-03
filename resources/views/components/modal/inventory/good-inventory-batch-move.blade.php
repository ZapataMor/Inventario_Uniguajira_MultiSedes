<div id="modalMoverMultiplesBienes" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalMoverMultiplesBienes')">&times;</span>
        <h2>Mover Bienes Seleccionados</h2>

        <input type="hidden" id="batchMoverInventarioOrigenId" />

        {{-- Selector de modo --}}
        <div class="batch-mode-toggle">
            <label class="batch-mode-option active" id="batchLabelMismo">
                <input type="radio" name="batchMode" value="mismo" checked
                       onchange="onBatchModeChange('mismo')">
                <i class="fas fa-layer-group"></i>
                Mismo destino
            </label>
            <label class="batch-mode-option" id="batchLabelDiferentes">
                <input type="radio" name="batchMode" value="diferentes"
                       onchange="onBatchModeChange('diferentes')">
                <i class="fas fa-random"></i>
                Destinos individuales
            </label>
        </div>

        {{-- Lista dinámica de bienes --}}
        <div class="form-section">
            <div class="section-header">Bienes a Trasladar</div>
            <div id="batchBienesList" class="batch-bienes-list"></div>
        </div>

        {{-- Destino global — solo visible en modo "mismo" --}}
        <div id="batchDestinoGlobal" class="form-section">
            <div class="section-header">Inventario Destino</div>
            <div class="form-fields-grid">
                <div class="form-field-full">
                    <label for="batchMoverGrupoDestino" class="form-label">Grupo:</label>
                    <select id="batchMoverGrupoDestino" class="form-input">
                        <option value="">Seleccionar grupo...</option>
                    </select>
                </div>
                <div class="form-field-full">
                    <label for="batchMoverInventarioDestino" class="form-label">Inventario:</label>
                    <select id="batchMoverInventarioDestino" class="form-input" disabled>
                        <option value="">Seleccionar inventario...</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="form-actions">
            <button type="button" class="submit-btn" onclick="submitBatchMove()">
                Mover Bienes
            </button>
        </div>
    </div>
</div>

<style>
/* ── Toggle de modo ─────────────────────────────────────────────────────── */
.batch-mode-toggle {
    display: flex;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 16px;
}

.batch-mode-option {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 14px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    background: #f8fafc;
    transition: background 0.2s, color 0.2s;
    user-select: none;
}

.batch-mode-option:first-child {
    border-right: 1px solid #e2e8f0;
}

.batch-mode-option input[type="radio"] {
    display: none;
}

.batch-mode-option.active {
    background: #059669;
    color: #fff;
}

/* ── Lista de bienes ─────────────────────────────────────────────────────── */
.batch-bienes-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.batch-bien-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.batch-bien-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.batch-bien-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
    min-width: 0;
}

.batch-bien-remove-btn {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}

.batch-bien-remove-btn:hover {
    background: #fee2e2;
    color: #dc2626;
}

.batch-bien-name {
    font-weight: 500;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.batch-bien-type {
    font-size: 12px;
    color: #64748b;
}

.batch-cantidad-input {
    width: 90px;
    text-align: center;
    flex-shrink: 0;
}

/* ── Destino por fila (modo "diferentes") ───────────────────────────────── */
.batch-row-destino {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}

.batch-row-destino select {
    font-size: 13px;
}

/* ── Lista de equipos seriales ──────────────────────────────────────────── */
.batch-serials-wrapper {
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}

.batch-serials-loading,
.batch-serials-empty {
    font-size: 13px;
    color: #94a3b8;
}

.batch-serials-toggle {
    font-size: 12px;
    margin-bottom: 6px;
    color: #64748b;
}

.batch-serials-toggle a {
    color: #059669;
    text-decoration: none;
}

.batch-serials-toggle a:hover {
    text-decoration: underline;
}

.batch-serials-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-height: 160px;
    overflow-y: auto;
    padding-right: 4px;
}

.batch-serial-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 6px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    color: #334155;
}

.batch-serial-item:hover {
    background: #f1f5f9;
}

.batch-serial-item input[type="checkbox"] {
    flex-shrink: 0;
    width: 15px;
    height: 15px;
    accent-color: #059669;
    cursor: pointer;
}

.serial-check-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
