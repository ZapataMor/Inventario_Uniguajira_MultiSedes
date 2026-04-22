<div id="modalCambiarInventarioBien" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalCambiarInventarioBien')">&times;</span>
        <h2>Cambiar de Inventario</h2>

        <form id="formCambiarInventarioBien" class="form-container" autocomplete="off"
            action="/api/goods-inventory/move-good" method="POST">
            @csrf
            <input type="hidden" id="moverBienId" name="bienId" />
            <input type="hidden" id="moverInventarioOrigenId" name="sourceInventarioId" />

            {{-- Información del bien --}}
            <div class="form-section">
                <div class="section-header">Información del Bien</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="moverNombreBien" class="form-label">Nombre del bien:</label>
                        <input type="text" id="moverNombreBien" class="form-input" disabled />
                    </div>
                </div>
            </div>

            {{-- Inventario destino --}}
            <div class="form-section">
                <div class="section-header">Inventario Destino</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="moverGrupoDestino" class="form-label">Grupo:</label>
                        <select id="moverGrupoDestino" class="form-input" required>
                            <option value="">Seleccionar grupo...</option>
                        </select>
                    </div>

                    <div class="form-field-full">
                        <label for="moverInventarioDestino" class="form-label">Inventario:</label>
                        <select id="moverInventarioDestino" name="destinoInventarioId" class="form-input" required disabled>
                            <option value="">Seleccionar inventario...</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Cantidad (solo para bienes tipo Cantidad) --}}
            <div id="moverCantidadSection" class="form-section" style="display:none;">
                <div class="section-header">Cantidad a Trasladar</div>

                <div class="form-fields-grid">
                    <div>
                        <label for="moverCantidadDisponible" class="form-label">Disponible:</label>
                        <input type="number" id="moverCantidadDisponible" class="form-input" disabled />
                    </div>
                    <div>
                        <label for="moverCantidad" class="form-label">Cantidad a trasladar:</label>
                        <input type="number" id="moverCantidad" name="cantidad" min="1" class="form-input" />
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
