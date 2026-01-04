<div id="modalRenombrarCarpeta" class="modal hidden">
    <div class="modal-content">

        <div class="modal-header">
            <h2>Renombrar carpeta</h2>
            <button onclick="cerrarModal('#modalRenombrarCarpeta')">&times;</button>
        </div>

        <div class="modal-body">
            <input
                type="text"
                id="renameFolderInput"
                placeholder="Nuevo nombre de la carpeta"
            />
        </div>

        <div class="modal-footer">
            <button class="btn-cancel" onclick="cerrarModal('#modalRenombrarCarpeta')">
                Cancelar
            </button>
            <button class="btn-confirm" onclick="renombrarCarpeta()">
                Guardar
            </button>
        </div>

    </div>
</div>
