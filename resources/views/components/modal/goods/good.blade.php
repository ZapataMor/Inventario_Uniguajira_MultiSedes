@props(['mode' => 'create'])

@php
    $isEdit = $mode === 'edit';

    $modalId = $isEdit ? 'modalActualizarBien' : 'modalCrearBien';
    $formId = $isEdit ? 'formActualizarBien' : 'formCrearBien';

    $title = $isEdit ? 'Actualizar Bien' : 'Nuevo Bien';

    $route = $isEdit ? route('goods.update') : route('goods.store');
@endphp

<div id="{{ $modalId }}" class="modal">
    <div class="modal-content modal-content-medium">

        <span class="close" onclick="ocultarModal('#{{ $modalId }}')">&times;</span>

        <h2>{{ $title }}</h2>

        <form
            id="{{ $formId }}"
            action="{{ $route }}"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
        >
            @csrf

            @if($isEdit)
                <input type="hidden" name="id" id="actualizarId">
            @endif

            {{-- Nombre --}}
            <div>
                <label for="{{ $isEdit ? 'actualizarNombreBien' : 'nombreBien' }}">Nombre:</label>
                <input
                    type="text"
                    name="nombre"
                    id="{{ $isEdit ? 'actualizarNombreBien' : 'nombreBien' }}"
                    required
                />
            </div>

            {{-- Tipo (solo creación) --}}
            @unless ($isEdit)
                <div>
                    <label for="tipoBien">Tipo:</label>
                    <select name="tipo" id="tipoBien" required>
                        <option value="1">Cantidad</option>
                        <option value="2">Serial</option>
                    </select>
                </div>
            @endunless

            {{-- Imagen --}}
            <div>
                <label for="{{ $isEdit ? 'actualizarImagenBien' : 'imagenBien' }}">
                    Imagen{{ $isEdit ? ' (opcional)' : '' }}:
                </label>
                <input
                    type="file"
                    name="imagen"
                    id="{{ $isEdit ? 'actualizarImagenBien' : 'imagenBien' }}"
                    accept="image/*"
                    class="
                        block w-full text-sm text-white
                        file:bg-[#6d7470]
                        file:text-white
                        file:border-0
                        file:rounded-md
                        file:px-3 file:py-1
                        file:cursor-pointer
                        file:mr-2
                        file:text-base
                        hover:file:bg-[#5f6663]
                        focus:outline-none
                    "
                />
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">
                    {{ $isEdit ? 'Guardar Cambios' : 'Guardar' }}
                </button>
            </div>
        </form>

    </div>
</div>
