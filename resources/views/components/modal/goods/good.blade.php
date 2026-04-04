{{-- Define la propiedad de entrada del componente y establece "create" como valor por defecto --}}
@props(['mode' => 'create'])

{{-- Calcula las variables dinámicas del modal según el modo: crear o editar --}}
@php
    $isEdit = $mode === 'edit';

    $modalId = $isEdit ? 'modalActualizarBien' : 'modalCrearBien';
    $formId = $isEdit ? 'formActualizarBien' : 'formCrearBien';

    $title = $isEdit ? 'Actualizar Bien' : 'Nuevo Bien';

    $route = $isEdit ? route('goods.update') : route('goods.store');
@endphp

{{-- Contenedor principal del modal con id dinámico según el modo --}}
<div
    id="{{ $modalId }}"
    class="modal"

{{-- En modo creación, indica que el formulario debe resetearse si se cierra sin guardar --}}
    @unless($isEdit)
        data-reset-on-close-without-save="true"
    @endunless
>

{{-- Contenido visual del modal --}}
    <div class="modal-content modal-content-medium">

{{-- Botón de cierre que ejecuta función JavaScript para ocultar el modal --}}
        <span class="close" onclick="ocultarModal('#{{ $modalId }}')">&times;</span>

{{-- Título dinámico del modal --}}
        <h2>{{ $title }}</h2>

{{-- Formulario para registrar o actualizar un bien --}}
        <form
            id="{{ $formId }}"
            action="{{ $route }}"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
        >

{{-- Token CSRF para proteger la petición contra ataques --}}
            @csrf

{{-- Campo oculto que almacena el id del bien en modo edición --}}
            @if($isEdit)
                <input type="hidden" name="id" id="actualizarId">
            @endif

{{-- Campo de texto para el nombre del bien (id dinámico según el modo) --}}
            <div>
                <label for="{{ $isEdit ? 'actualizarNombreBien' : 'nombreBien' }}">Nombre:</label>
                <input
                    type="text"
                    name="nombre"
                    id="{{ $isEdit ? 'actualizarNombreBien' : 'nombreBien' }}"
                    required
                />
            </div>

{{-- Selector de tipo del bien (solo disponible en modo creación) --}}
            @unless ($isEdit)
                <div>
                    <label for="tipoBien">Tipo:</label>
                    <select name="tipo" id="tipoBien" required>
                        <option value="1">Cantidad</option>
                        <option value="2">Serial</option>
                    </select>
                </div>
            @endunless

{{-- Campo para cargar o actualizar la imagen del bien --}}
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

{{-- Contenedor de acciones del formulario --}}
            <div class="form-actions">
                <button type="submit" class="btn submit-btn">
                    {{ $isEdit ? 'Guardar Cambios' : 'Guardar' }}
                </button>
            </div>
        </form>

    </div>
</div>