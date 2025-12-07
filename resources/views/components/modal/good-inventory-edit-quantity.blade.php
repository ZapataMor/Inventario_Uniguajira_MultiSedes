<div id="modalEditarBienCantidad" class="modal">
    <div class="modal-content">
        <span class="close" onclick="ocultarModal('#modalEditarBienCantidad')">&times;</span>
        <h2>Editar Cantidad</h2>
        <form id="formEditarBienCantidad" class="form-container" autocomplete="off" action="/api/goods-inventory/update-quantity" method="POST">
            @csrf
            <input type="hidden" id="editBienId" name="bienId" />
            <input type="hidden" id="editInventarioId" name="inventarioId" />

            <!-- Sección de información del bien -->
            <div class="form-section">
                <div class="section-header">Información del Bien</div>
                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="editNombreBienCantidad" class="form-label">Nombre del Bien:</label>
                        <input type="text" id="editNombreBienCantidad" class="form-input" disabled />
                    </div>
                </div>
            </div>

            <!-- Campos para editar la cantidad -->
            <div class="form-section">
                <div class="section-header">Detalles de Cantidad</div>
                <div class="form-fields-grid">
                    <div>
                        <label for="editCantidadBien" class="form-label">Cantidad:</label>
                        <input type="number" name="cantidad" id="editCantidadBien" min="1" class="form-input" required />
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn submit-btn">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
