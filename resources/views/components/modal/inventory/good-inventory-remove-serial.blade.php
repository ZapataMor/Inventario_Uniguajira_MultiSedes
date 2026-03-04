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