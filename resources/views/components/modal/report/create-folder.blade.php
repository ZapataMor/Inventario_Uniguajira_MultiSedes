<div id="modalCrearCarpeta" class="modal hidden">
    <div class="modal-content">

        <div class="modal-header">
            <h2>Crear carpeta</h2>
            <button onclick="cerrarModal('#modalCrearCarpeta')">&times;</button>
        </div>

        <div class="modal-body">
            <input
                type="text"
                id="folderName"
                placeholder="Nombre de la carpeta"
            />
        </div>

        <div class="modal-footer">
            <button class="btn-cancel" onclick="cerrarModal('#modalCrearCarpeta')">
                Cancelar
            </button>
            <button class="btn-confirm" onclick="crearCarpeta()">
                Crear
            </button>
        </div>

    </div>
</div>
