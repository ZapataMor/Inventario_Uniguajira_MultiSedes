<div class="form-section">
    <div class="section-header">Tipo de Bien</div>

    <div class="form-fields-grid">
        <div class="form-field-full">
            <label class="form-label">Seleccione el tipo:</label>
            <select id="filterType" class="form-input">
                <option value="all">Todos los tipos</option>
                <option value="Cantidad">Cantidad</option>
                <option value="Serial">Serial</option>
            </select>
        </div>
    </div>
</div>


{{-- 2) UBICACIÓN --}}
<div class="form-section">
    <div class="section-header">Ubicación</div>

    <div class="form-fields-grid">
        <div>
            <label class="form-label">Bloque:</label>
            <select id="filterGroup" class="form-input" onchange="updateInventoryOptions(this.value)">
                <option value="">Todos los bloques</option>
            </select>
        </div>

        <div>
            <label class="form-label">Inventario:</label>
            <select id="filterInventory" class="form-input">
                <option value="">Todos los inventarios</option>
            </select>
        </div>
    </div>
</div>


{{-- 3) RANGO DE FECHAS --}}
<div class="form-section">
    <div class="section-header">Rango de Fechas</div>

    <div class="form-fields-grid">
        <div>
            <label class="form-label">Fecha Desde:</label>
            <input type="date" id="filterDateFrom" class="form-input" />
        </div>

        <div>
            <label class="form-label">Fecha Hasta:</label>
            <input type="date" id="filterDateTo" class="form-input" />
        </div>
    </div>
</div>


{{-- 4) ACCIONES --}}
<div class="form-actions">
    <button type="button" class="cancel-btn" onclick="ocultarModal('#modalFilterRemoved')">
        <i class="fas fa-times"></i> Cancelar
    </button>

    <button type="button" class="submit-btn" onclick="applyFilters()">
        <i class="fas fa-check"></i> Aplicar Filtros
    </button>
</div>