<div id="modalCrearCarpeta" class="modal">
    <div class="modal-content modal-content-medium">
        <span class="close" onclick="ocultarModal('#modalCrearCarpeta')">&times;</span>

        <h2>Nueva carpeta</h2>

        <form id="formCrearCarpeta" action="{{ url('/api/folders/create') }}" method="POST" autocomplete="off">
            @csrf

            <div>
                <label for="nombreCarpeta">Nombre de la carpeta:</label>
                <input type="text" id="nombreCarpeta" name="nombreCarpeta" required />
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">Guardar</button>
            </div>
        </form>
    </div>
</div>

