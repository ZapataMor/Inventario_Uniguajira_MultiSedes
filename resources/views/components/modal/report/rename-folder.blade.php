<div id="modalRenombrarCarpeta" class="modal">
    <div class="modal-content modal-content-medium">
        <span class="close" onclick="ocultarModal('#modalRenombrarCarpeta')">&times;</span>

        <h2>Renombrar carpeta</h2>

        <form id="formRenombrarCarpeta" action="{{ url('/api/folders/rename') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" id="carpetaRenombrarId" name="folder_id" />

            <div>
                <label for="carpetaRenombrarNombre">Nuevo nombre:</label>
                <input type="text" id="carpetaRenombrarNombre" name="nombre" required />
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

