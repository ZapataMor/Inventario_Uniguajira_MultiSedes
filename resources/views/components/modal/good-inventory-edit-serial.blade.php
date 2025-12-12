<div id="modalEditarBienSerial" class="modal">
    <div class="modal-content modal-content-large">
        <span class="close" onclick="ocultarModal('#modalEditarBienSerial')">&times;</span>
        <h2>Editar Bien</h2>
        <form id="formEditarBienSerial" class="form-container" autocomplete="off" action="/api/goods-inventory/update-serial" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="editBienEquipoId" name="bienEquipoId" />

            <!-- Sección de información del bien -->
            <div class="form-section">
                <div class="section-header">Información del Bien</div>
                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="editNombreBien" class="form-label">Nombre del Bien:</label>
                        <input type="text" id="editNombreBien" class="form-input" disabled />
                    </div>
                </div>
            </div>

            <!-- Campos para editar el bien serial -->
            <div class="form-section">
                <div class="section-header">Detalles del Bien</div>

                <!-- Primera fila: Descripción en toda la fila -->
                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="editDescripcionBien" class="form-label">Descripción:</label>
                        <textarea name="descripcion" id="editDescripcionBien" class="form-input"></textarea>
                    </div>
                </div>

                <!-- Segunda fila: Marca y Modelo -->
                <div class="form-fields-grid form-row">
                    <div>
                        <label for="editMarcaBien" class="form-label">Marca:</label>
                        <input type="text" name="marca" id="editMarcaBien" class="form-input" />
                    </div>

                    <div>
                        <label for="editModeloBien" class="form-label">Modelo:</label>
                        <input type="text" name="modelo" id="editModeloBien" class="form-input" />
                    </div>
                </div>

                <!-- Tercera fila: Serial y Estado -->
                <div class="form-fields-grid form-row">
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

                <!-- Cuarta fila: Color, Condición y Fecha -->
                <div class="form-fields-grid form-row">
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
                        <input type="date" name="fecha_ingreso" id="editFechaIngresoBien" class="form-input" readonly />
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn submit-btn">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
