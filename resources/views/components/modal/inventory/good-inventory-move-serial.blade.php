<div id="modalCambiarInventarioSerial" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalCambiarInventarioSerial')">&times;</span>
        <h2>Cambiar de Inventario</h2>

        <form id="formCambiarInventarioSerial" class="form-container" autocomplete="off"
            action="/api/goods-inventory/move-serial" method="POST">
            @csrf
            <input type="hidden" id="moverSerialEquipoId" name="equipmentId" />

            {{-- Información del bien --}}
            <div class="form-section">
                <div class="section-header">Información del Bien</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="moverSerialNombreBien" class="form-label">Nombre del bien:</label>
                        <input type="text" id="moverSerialNombreBien" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="moverSerialSerial" class="form-label">Serial:</label>
                        <input type="text" id="moverSerialSerial" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="moverSerialMarca" class="form-label">Marca:</label>
                        <input type="text" id="moverSerialMarca" class="form-input" disabled />
                    </div>
                </div>
            </div>

            {{-- Inventario destino --}}
            <div class="form-section">
                <div class="section-header">Inventario Destino</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="moverSerialGrupoDestino" class="form-label">Grupo:</label>
                        <select id="moverSerialGrupoDestino" class="form-input" required>
                            <option value="">Seleccionar grupo...</option>
                        </select>
                    </div>

                    <div class="form-field-full">
                        <label for="moverSerialInventarioDestino" class="form-label">Inventario:</label>
                        <select id="moverSerialInventarioDestino" name="destinoInventarioId" class="form-input" required disabled>
                            <option value="">Seleccionar inventario...</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="form-actions">
                <button type="submit" class="submit-btn">Cambiar de Inventario</button>
            </div>
        </form>
    </div>
</div>
