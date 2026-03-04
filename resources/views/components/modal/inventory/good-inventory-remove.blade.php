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

<div id="modalDarDeBajaBienCantidad" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalDarDeBajaBienCantidad')">&times;</span>
        <h2>Dar de Baja Bien</h2>

        <form id="formDarDeBajaBienCantidad" class="form-container" autocomplete="off"
            action="/api/goods-inventory/remove-good" method="POST">
            @csrf
            <input type="hidden" id="darDeBajaBienId" name="bienId" />
            <input type="hidden" id="darDeBajaInventarioId" name="inventarioId" />

            {{-- 1) INFORMACIÓN DEL BIEN --}}
            <div class="form-section">
                <div class="section-header">Información del Bien</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="darDeBajaNombreBienCantidad" class="form-label">Nombre del bien:</label>
                        <input type="text" id="darDeBajaNombreBienCantidad" class="form-input" disabled />
                    </div>
                </div>
            </div>

            {{-- 2) CANTIDAD A DAR DE BAJA --}}
            <div class="form-section">
                <div class="section-header">Cantidad a Dar de Baja</div>

                <div class="form-fields-grid">
                    <div>
                        <label for="darDeBajaCantidadDisponible" class="form-label">Cantidad disponible:</label>
                        <input type="number" id="darDeBajaCantidadDisponible" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaCantidadBien" class="form-label">Cantidad a dar de baja:</label>
                        <input type="number" name="cantidad" id="darDeBajaCantidadBien"
                            min="1" class="form-input" required />
                    </div>
                </div>
            </div>

            {{-- 3) MOTIVO --}}
            <div class="form-section">
                <div class="section-header">Motivo de Baja (Opcional)</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="darDeBajaMotivo" class="form-label">Motivo:</label>
                        <textarea name="motivo" id="darDeBajaMotivo" class="form-input"
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