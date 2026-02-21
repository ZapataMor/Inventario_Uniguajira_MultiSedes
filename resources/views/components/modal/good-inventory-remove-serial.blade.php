@once
<style>
    /* ── Flyout Modal ─────────────────────────────────────────────── */
    .modal.flyout-modal {
        position: fixed !important;
        top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100vw !important; height: 100vh !important;
        background: rgba(0, 0, 0, 0.45);
        z-index: 9999 !important;
        margin: 0 !important; padding: 0 !important;
        opacity: 0; visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .modal.flyout-modal.active {
        opacity: 1 !important; visibility: visible !important;
    }
    .modal.flyout-modal > .flyout-panel {
        position: fixed !important;
        top: 0; right: 0; bottom: 0;
        height: 100vh !important;
        width: 42% !important; max-width: 680px !important; min-width: 380px !important;
        background: #fff;
        border-radius: 0 !important;
        overflow-y: auto; overflow-x: hidden;
        margin: 0 !important; padding: 30px 28px !important;
        box-sizing: border-box !important;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform;
        box-shadow: -4px 0 24px rgba(0, 0, 0, 0.14);
    }
    .modal.flyout-modal.active > .flyout-panel {
        transform: translateX(0) !important;
    }
    .flyout-panel > .close {
        position: absolute; top: 18px; right: 22px;
        font-size: 26px; font-weight: 700; color: #9ca3af;
        cursor: pointer; line-height: 1; background: none;
        border: none; padding: 0; transition: color 0.2s; z-index: 10;
    }
    .flyout-panel > .close:hover { color: #111827; }
    .flyout-panel > h2 { margin: 0 0 22px; padding-right: 40px; font-size: 20px; font-weight: 700; }
    @media (max-width: 768px) {
        .modal.flyout-modal > .flyout-panel { width: 92% !important; min-width: 0 !important; }
    }

    /* ── Inputs, selects y textareas ──────────────────────────────── */
    .flyout-panel input,
    .flyout-panel select,
    .flyout-panel textarea {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background-color: #FDFDFD;
        font-size: 1rem;
        color: #333;
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .flyout-panel input:focus,
    .flyout-panel select:focus,
    .flyout-panel textarea:focus {
        border-color: #d7d7d7;
        box-shadow: 0 0 0 2px rgba(47, 44, 45, 0.1);
        outline: none;
    }
    .flyout-panel input:disabled,
    .flyout-panel textarea:disabled {
        background-color: #f5f5f5;
        color: #666;
        cursor: not-allowed;
    }

    /* ── Espaciado entre grids consecutivos dentro de una sección ─── */
    .flyout-panel .form-fields-grid + .form-fields-grid {
        margin-top: 15px;
    }
</style>
@endonce

<div id="modalDarDeBajaBienSerial" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalDarDeBajaBienSerial')">&times;</span>
        <h2>Dar de Baja Bien Serial</h2>

        <form id="formDarDeBajaBienSerial" class="form-container" autocomplete="off"
            action="/api/goods-inventory/remove-good-serial" method="POST">
            @csrf
            <input type="hidden" id="darDeBajaSerialEquipoId" name="equipmentId" />
            <input type="hidden" id="darDeBajaSerialInventarioId" name="inventarioId" />

            {{-- 1) INFORMACIÓN DEL BIEN --}}
            <div class="form-section">
                <div class="section-header">Información del Bien</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="darDeBajaSerialNombreBien" class="form-label">Nombre del bien:</label>
                        <input type="text" id="darDeBajaSerialNombreBien" class="form-input" disabled />
                    </div>
                </div>

                <div class="form-fields-grid">
                    <div>
                        <label for="darDeBajaSerialSerial" class="form-label">Serial:</label>
                        <input type="text" id="darDeBajaSerialSerial" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaSerialEstado" class="form-label">Estado:</label>
                        <input type="text" id="darDeBajaSerialEstado" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaSerialMarca" class="form-label">Marca:</label>
                        <input type="text" id="darDeBajaSerialMarca" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaSerialModelo" class="form-label">Modelo:</label>
                        <input type="text" id="darDeBajaSerialModelo" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaSerialColor" class="form-label">Color:</label>
                        <input type="text" id="darDeBajaSerialColor" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaSerialCondicion" class="form-label">Condición técnica:</label>
                        <input type="text" id="darDeBajaSerialCondicion" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaSerialFechaIngreso" class="form-label">Fecha de ingreso:</label>
                        <input type="text" id="darDeBajaSerialFechaIngreso" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaSerialFechaSalida" class="form-label">Fecha de salida:</label>
                        <input type="text" id="darDeBajaSerialFechaSalida" class="form-input" disabled />
                    </div>
                </div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="darDeBajaSerialDescripcion" class="form-label">Descripción:</label>
                        <textarea id="darDeBajaSerialDescripcion" class="form-input"
                            rows="3" disabled></textarea>
                    </div>
                </div>
            </div>

            {{-- 2) MOTIVO --}}
            <div class="form-section">
                <div class="section-header">Motivo de Baja (Opcional)</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="darDeBajaSerialMotivo" class="form-label">Motivo:</label>
                        <textarea name="motivo" id="darDeBajaSerialMotivo" class="form-input"
                            rows="3" placeholder="Deterioro, pérdida, transferencia, etc."></textarea>
                    </div>
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="form-actions">
                <button type="submit" class="submit-btn">Dar de Baja</button>
            </div>
        </form>
    </div>
</div>