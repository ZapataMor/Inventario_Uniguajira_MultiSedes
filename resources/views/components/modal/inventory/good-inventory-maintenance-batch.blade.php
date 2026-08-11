{{--
    Mantenimiento masivo de bienes seriales.

    Se abre desde la barra de multi-selección de la vista de seriales, con dos
    o más bienes marcados. Todos pertenecen al mismo bien, así que el
    mantenimiento describe una sola labor repetida sobre varios equipos.
--}}
@if(Auth::user()->isAdministrator())
<div id="modalMantenimientoMasivo" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalMantenimientoMasivo')">&times;</span>
        <h2>Registrar mantenimiento</h2>

        <div class="form-container">
            {{-- 1) BIENES SELECCIONADOS --}}
            <div class="form-section">
                <div class="section-header">
                    Bienes seleccionados
                    <span class="mant-batch-count" id="mantMasivoResumen">0 bienes</span>
                </div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="mantMasivoBien" class="form-label">Bien:</label>
                        <input type="text" id="mantMasivoBien" class="form-input" disabled />
                    </div>
                </div>

                <ul class="mant-batch-list" id="mantMasivoLista"></ul>
            </div>

            {{-- 2) DATOS DEL MANTENIMIENTO --}}
            <div class="form-section">
                <div class="section-header">Datos del mantenimiento</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="mantMasivoTitulo" class="form-label">
                            Título <span class="required">*</span>
                        </label>
                        <input type="text" id="mantMasivoTitulo" class="form-input"
                               maxlength="255" placeholder="Ej: Limpieza general" />
                    </div>

                    <div class="form-field-full">
                        <label for="mantMasivoFecha" class="form-label">
                            Fecha <span class="required">*</span>
                        </label>
                        <input type="date" id="mantMasivoFecha" class="form-input" />
                    </div>

                    <div class="form-field-full">
                        <label for="mantMasivoDescripcion" class="form-label">Descripción</label>
                        <textarea id="mantMasivoDescripcion" class="form-input mant-textarea"
                                  rows="3" placeholder="Describe el mantenimiento realizado..."></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="submit-btn" id="mantMasivoSubmit"
                        onclick="submitMantenimientoMasivo()">
                    Registrar mantenimiento
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.mant-batch-count {
    margin-left: 8px;
    padding: 2px 9px;
    border-radius: 999px;
    background: #d1fae5;
    color: #065f46;
    font-size: 12px;
    font-weight: 700;
}

.mant-batch-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 200px;
    overflow-y: auto;
    margin: 12px 0 0;
    padding: 0 4px 0 0;
}

.mant-batch-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}

.mant-batch-serial {
    color: #1e293b;
    font-size: 13px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.mant-batch-brand {
    flex-shrink: 0;
    color: #64748b;
    font-size: 12px;
}
</style>
@endif
