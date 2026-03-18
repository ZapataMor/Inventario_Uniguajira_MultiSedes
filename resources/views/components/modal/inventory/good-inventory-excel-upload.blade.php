<div id="modalExcelInventario" class="modal">
    <div class="modal-content modal-content-large scrollable-content">
        <span class="close" onclick="ocultarModal('#modalExcelInventario')">&times;</span>
        <h2>Carga masiva de bienes</h2>

        <div class="goods-header" style="display: flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
            <p style="color: #555; font-size: 0.9rem;">
                Sube un archivo Excel con los bienes a agregar a este inventario.<br>
                Los bienes que no existan en el catalogo seran creados automaticamente.
            </p>

            <button class="excel-upload-btn" title="Descargar plantilla Excel"
                onclick="descargarPlantillaInventario()"
                style="display:flex; flex-direction:column; align-items:center; gap:4px; flex-shrink:0;">
                <i class="fas fa-download"></i>
                <span>Plantilla</span>
            </button>
        </div>

        <x-excel-upload-area
            area-id="inv-excel-upload-area"
            input-id="invExcelFileInput"
            accept=".xlsx,.xls"
            prompt="Arrastra y suelta un archivo aqui o haz clic para seleccionar"
            button-text="Seleccionar archivo"
        />

        <x-excel-preview-table
            title="Previsualizacion"
            container-id="inv-excel-preview-table"
            table-id="invPreviewTable"
            body-id="invPreviewBody"
            :columns="[
                ['label' => 'Bien'],
                ['label' => 'Tipo'],
                ['label' => 'Serial'],
                ['label' => 'Cantidad'],
                ['label' => 'Marca'],
                ['label' => 'Modelo'],
                ['label' => 'Estado'],
                ['label' => ''],
            ]"
            clear-button-id="btnLimpiarExcelInventario"
            submit-button-id="btnEnviarExcelInventario"
            error-list-id="invErrorList"
            error-items-id="invErrorItems"
            error-title="Errores:"
        />
    </div>
</div>
