<div id="modalMantenimientos" class="modal flyout-modal">
    <div class="flyout-panel">
        {{-- close oculto para no solaparse; el cierre real está en el header --}}
        <span class="close" onclick="ocultarModal('#modalMantenimientos')" style="display:none"></span>

        <div class="mant-modal-header">
            <h2>Mantenimientos</h2>
            <div class="mant-modal-actions">
                @if(Auth::user()->isAdministrator())
                <button type="button" class="mant-add-btn" id="mantAddBtn"
                        onclick="toggleMantForm()" title="Registrar mantenimiento">
                    <i class="fas fa-plus"></i>
                </button>
                @endif
                <button type="button" class="mant-close-btn" onclick="ocultarModal('#modalMantenimientos')" title="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <input type="hidden" id="mantenimientoAssetId" />
        <input type="hidden" id="mantenimientoInventoryId" />

        @if(Auth::user()->isAdministrator())
        {{-- Formulario colapsable --}}
        <div id="mantFormSection" class="mant-form-section" style="display:none;">
            <div class="form-fields-grid">
                <div class="form-field-full">
                    <label for="mantTitulo" class="form-label">Título <span class="required">*</span></label>
                    <input type="text" id="mantTitulo" class="form-input" placeholder="Ej: Limpieza general" maxlength="255" />
                </div>
                <div class="form-field-full">
                    <label for="mantFecha" class="form-label">Fecha <span class="required">*</span></label>
                    <input type="date" id="mantFecha" class="form-input" />
                </div>
                <div class="form-field-full">
                    <label for="mantDescripcion" class="form-label">Descripción</label>
                    <textarea id="mantDescripcion" class="form-input mant-textarea" rows="3" placeholder="Describe el mantenimiento realizado..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="submit-btn" onclick="submitMantenimiento()">
                    Guardar mantenimiento
                </button>
            </div>
        </div>
        @endif

        {{-- Lista de mantenimientos --}}
        <div class="form-section">
            <div class="section-header">Historial</div>
            <div id="mantenimientosList" class="mant-list">
                <div class="mant-empty">Cargando...</div>
            </div>
        </div>
    </div>
</div>

<style>
.mant-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.mant-modal-header h2 {
    margin: 0;
}

.mant-modal-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.mant-close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #94a3b8;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}

.mant-close-btn:hover {
    background: #f1f5f9;
    color: #475569;
}

.mant-add-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: none;
    background: #059669;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.15s, transform 0.15s;
    flex-shrink: 0;
}

.mant-add-btn:hover {
    background: #047857;
}

.mant-add-btn.active {
    background: #64748b;
    transform: rotate(45deg);
}

.mant-form-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
}

.mant-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 340px;
    overflow-y: auto;
    padding-right: 4px;
}

.mant-empty {
    text-align: center;
    color: #94a3b8;
    font-size: 14px;
    padding: 20px 0;
}

.mant-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    position: relative;
}

.mant-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.mant-item-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
}

.mant-item-date {
    font-size: 12px;
    color: #64748b;
    white-space: nowrap;
}

.mant-item-desc {
    font-size: 13px;
    color: #475569;
    white-space: pre-wrap;
}

.mant-item-by {
    font-size: 11px;
    color: #94a3b8;
}

.mant-delete-btn {
    background: transparent;
    border: none;
    color: #cbd5e1;
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 4px;
    transition: color 0.15s, background 0.15s;
    flex-shrink: 0;
}

.mant-delete-btn:hover {
    color: #dc2626;
    background: #fee2e2;
}

.mant-textarea {
    resize: vertical;
    min-height: 72px;
}

.required {
    color: #dc2626;
}
</style>
