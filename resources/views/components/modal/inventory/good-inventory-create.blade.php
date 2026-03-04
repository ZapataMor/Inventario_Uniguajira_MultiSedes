<div id="modalCrearBienInventario" class="modal">
    <div class="modal-content modal-content-large scrollable-content">
        <span class="close" onclick="ocultarModal('#modalCrearBienInventario')">&times;</span>
        <h2>Nuevo Bien</h2>
        <form id="formCrearBienInventario" class="form-container" autocomplete="off" action="/api/goods-inventory/create" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="inventarioId" name="inventarioId" />

            <!-- Sección de búsqueda con diseño generalizado -->
            <div class="form-section">
                <div class="section-header">Seleccionar Bien</div>
                <div id="search-container" class="search-wrapper">
                    <input type="hidden" id="bien_id" name="bien_id" />
                    <input type="hidden" id="bien_tipo" name="bien_tipo" />
                    <input type="text" name="nombre" id="nombreBienEnInventario" class="form-input" placeholder="Buscar bien en el catálogo..." required />
                    <ul class="suggestions"></ul>
                </div>
            </div>

            <!-- Campos dinámicos con diseño generalizado -->
            <div class="" id="dynamicFields" style="display: none;">
                <!-- Campo para bienes de tipo Cantidad -->
                <div id="camposCantidad" class="form-section fade-in-up" style="display: none;">
                    <div class="section-header">Detalles de Cantidad</div>
                    <div class="form-fields-grid">
                        <div>
                            <label for="cantidadBien" class="form-label">
                                <span class="text-red-600 font-semibold">*</span>
                                Cantidad:
                            </label>
                            <input type="number" name="cantidad" id="cantidadBien" min="1" value="1" class="form-input" />
                        </div>
                    </div>
                </div>

                <!-- Campos para bienes de tipo Serial -->
                <div id="camposSerial" class="form-section fade-in-up" style="display: none;">
                    <div class="section-header">Detalles del Bien</div>

                    <!-- Primera fila: Descripción en toda la fila -->
                    <div class="form-fields-grid">
                        <div class="form-field-full">
                            <label for="descripcionBien" class="form-label">Descripción:</label>
                            <textarea name="descripcion" id="descripcionBien" class="form-input"></textarea>
                        </div>
                    </div>

                    <!-- Segunda fila: Marca y Modelo -->
                    <div class="form-fields-grid form-row">
                        <div>
                            <label for="marcaBien" class="form-label">Marca:</label>
                            <input type="text" name="marca" id="marcaBien" class="form-input" />
                        </div>

                        <div>
                            <label for="modeloBien" class="form-label">Modelo:</label>
                            <input type="text" name="modelo" id="modeloBien" class="form-input" />
                        </div>
                    </div>

                    <!-- Tercera fila: Serial y Estado -->
                    <div class="form-fields-grid form-row">
                        <div>
                            <label for="serialBien" class="form-label">
                                <span class="text-red-600 font-semibold">*</span>
                                Serial:
                            </label>
                            <input type="text" name="serial" id="serialBien" class="form-input" />
                        </div>

                        <div>
                            <label for="estadoBien" class="form-label">Estado:</label>
                            <select name="estado" id="estadoBien" class="form-input">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="en_mantenimiento">En mantenimiento</option>
                            </select>
                        </div>
                    </div>

                    <!-- Cuarta fila: Color, Condición y Fecha -->
                    <div class="form-fields-grid form-row">
                        <div>
                            <label for="colorBien" class="form-label">Color:</label>
                            <input type="text" name="color" id="colorBien" class="form-input" />
                        </div>

                        <div>
                            <label for="condicionBien" class="form-label">Condición técnica:</label>
                            <input type="text" name="condicion_tecnica" id="condicionBien" class="form-input" />
                        </div>

                        <div>
                            <label for="fechaIngresoBien" class="form-label">Fecha de ingreso:</label>
                            <input type="date" name="fecha_ingreso" id="fechaIngresoBien" class="form-input" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">Guardar Bien</button>
            </div>
        </form>
    </div>
</div>
