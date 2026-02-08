<div id="modalDarDeBajaBienCantidad" class="modal">
    <div class="modal-content">
        <span class="close" onclick="ocultarModal('#modalDarDeBajaBienCantidad')">&times;</span>
        <h2>Dar de Baja Bien</h2>
        <form id="formDarDeBajaBienCantidad" class="form-container" autocomplete="off" 
              action="/api/goods-inventory/remove-good" method="POST">
            @csrf
            <input type="hidden" id="darDeBajaBienId" name="bienId" />
            <input type="hidden" id="darDeBajaInventarioId" name="inventarioId" />

            <!-- Sección de información del bien -->
            <div class="form-section">
                <div class="section-header">Información del Bien</div>
                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="darDeBajaNombreBienCantidad" class="form-label">Nombre del Bien:</label>
                        <input type="text" id="darDeBajaNombreBienCantidad" class="form-input" disabled />
                    </div>
                </div>
            </div>

            <!-- Campos para dar de baja -->
            <div class="form-section">
                <div class="section-header">Cantidad a Dar de Baja</div>
                <div class="form-fields-grid">
                    <div>
                        <label for="darDeBajaCantidadDisponible" class="form-label">Cantidad Disponible:</label>
                        <input type="number" id="darDeBajaCantidadDisponible" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="darDeBajaCantidadBien" class="form-label">Cantidad a Dar de Baja:</label>
                        <input type="number" name="cantidad" id="darDeBajaCantidadBien" 
                               min="1" class="form-input" required />
                    </div>
                </div>
            </div>

            <!-- Campo de motivo -->
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

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn submit-btn">Dar de Baja</button>
            </div>
        </form>
    </div>
</div>