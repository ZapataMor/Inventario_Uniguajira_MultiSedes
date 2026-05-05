@props(['mode' => 'responsable'])

@php
    // Fixed mode: responsable
    $modalId = 'modalEditarResponsable';
    $formId = 'formEditarResponsable';
    $title = 'Editar Responsable';
    $route = url('/api/inventories/updateResponsable');
@endphp

<div id="{{ $modalId }}" class="modal">
    <div class="modal-content modal-content-small">

        <span class="close" onclick="ocultarModal('#{{ $modalId }}')">&times;</span>

        <h2>{{ $title }}</h2>

        <form id="{{ $formId }}" action="{{ $route }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="id" id="editarResponsableId" />

            <div class="responsable-actual-row">
                <span class="responsable-label">Responsable:</span>
                <span class="responsable-valor" id="responsableActualTexto">—</span>
            </div>

            <div>
                <label for="editarResponsableNombre">Nuevo responsable</label>
                <input type="text" name="responsable" id="editarResponsableNombre" placeholder="Escribe el nuevo responsable..." />
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">Guardar Cambios</button>
            </div>
        </form>

    </div>
</div>
