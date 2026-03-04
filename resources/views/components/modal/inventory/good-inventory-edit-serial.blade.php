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
</style>
@endonce

<div id="modalEditarBienSerial" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalEditarBienSerial')">&times;</span>
        <h2>Editar Bien</h2>

        <form id="formEditarBienSerial" class="form-container" autocomplete="off"
            action="/api/goods-inventory/update-serial" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="editBienEquipoId" name="bienEquipoId" />

            {{-- 1) INFORMACIÓN DEL BIEN --}}
            <div class="form-section">
                <div class="section-header">Información del Bien</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="editNombreBien" class="form-label">Nombre del Bien:</label>
                        <input type="text" id="editNombreBien" class="form-input" disabled />
                    </div>
                </div>
            </div>

            {{-- 2) DETALLES DEL BIEN --}}
            <div class="form-section">
                <div class="section-header">Detalles del Bien</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="editDescripcionBien" class="form-label">Descripción:</label>
                        <textarea name="descripcion" id="editDescripcionBien" class="form-input"></textarea>
                    </div>
                </div>

                <div class="form-fields-grid">
                    <div>
                        <label for="editMarcaBien" class="form-label">Marca:</label>
                        <input type="text" name="marca" id="editMarcaBien" class="form-input" />
                    </div>
                    <div>
                        <label for="editModeloBien" class="form-label">Modelo:</label>
                        <input type="text" name="modelo" id="editModeloBien" class="form-input" />
                    </div>
                </div>

                <div class="form-fields-grid">
                    <div>
                        <label for="editSerialBien" class="form-label">Serial:</label>
                        <input type="text" name="serial" id="editSerialBien" class="form-input" />
                    </div>
                    <div>
                        <label for="editEstadoBien" class="form-label">Estado:</label>
                        <select name="estado" id="editEstadoBien" class="form-input">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="en_mantenimiento">En mantenimiento</option>
                        </select>
                    </div>
                </div>

                <div class="form-fields-grid">
                    <div>
                        <label for="editColorBien" class="form-label">Color:</label>
                        <input type="text" name="color" id="editColorBien" class="form-input" />
                    </div>
                    <div>
                        <label for="editCondicionBien" class="form-label">Condición técnica:</label>
                        <input type="text" name="condicion_tecnica" id="editCondicionBien" class="form-input" />
                    </div>
                    <div>
                        <label for="editFechaIngresoBien" class="form-label">Fecha de ingreso:</label>
                        <input type="date" name="fecha_ingreso" id="editFechaIngresoBien"
                            class="form-input" readonly />
                    </div>
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="form-actions">
                <button type="submit" class="submit-btn">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>