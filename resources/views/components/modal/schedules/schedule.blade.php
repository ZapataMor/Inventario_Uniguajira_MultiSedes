@props([
    'mode' => 'create',
    'inventories' => collect(),
])

@php
    $isCreate = $mode === 'create';
    $id = $isCreate ? 'modalCrearProgramacion' : 'modalEditarProgramacion';
    $formId = $isCreate ? 'formCrearProgramacion' : 'formEditarProgramacion';
    $prefix = $isCreate ? 'crearProgramacion' : 'editarProgramacion';
    $action = $isCreate ? url('/api/schedules/create') : url('/api/schedules/update');
@endphp

<div id="{{ $id }}" class="modal" data-reset-on-close-without-save="true">
    <div class="modal-content modal-content-medium">
        <span class="close" onclick="ocultarModal('#{{ $id }}')">&times;</span>
        <h2>{{ $isCreate ? 'Nueva programación' : 'Editar programación' }}</h2>

        <form id="{{ $formId }}" action="{{ $action }}" method="POST" autocomplete="off">
            @csrf
            @unless($isCreate)
                <input type="hidden" id="{{ $prefix }}Id" name="id">
            @endunless

            <div>
                <label for="{{ $prefix }}Title">Nombre de la programación:</label>
                <input type="text" id="{{ $prefix }}Title" name="title" maxlength="255" required
                       placeholder="Ej: Mantenimiento preventivo sala de sistemas">
            </div>

            <div>
                <label for="{{ $prefix }}Inventory">Ubicación (opcional):</label>
                <select id="{{ $prefix }}Inventory" name="inventory_id">
                    <option value="">Sin ubicación asignada</option>
                    @foreach($inventories as $inventory)
                        <option value="{{ $inventory->id }}">
                            {{ $inventory->group?->name ? $inventory->group->name . ' · ' : '' }}{{ $inventory->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">
                    {{ $isCreate ? 'Crear programación' : 'Guardar cambios' }}
                </button>
            </div>
        </form>
    </div>
</div>
