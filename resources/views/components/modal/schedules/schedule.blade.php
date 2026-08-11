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

            {{--
                Una misma labor puede cubrir varias ubicaciones, así que la
                selección es múltiple. Se usan casillas y no un <select multiple>
                porque la lista suele ser larga y necesita búsqueda.
            --}}
            <div class="sched-locations-field">
                <label>Ubicaciones (opcional):</label>

                @if($inventories->isEmpty())
                    <p class="sched-locations-empty">
                        Todavía no hay inventarios registrados en esta sede.
                    </p>
                @else
                    <input type="text" class="sched-locations-search"
                           data-locations-search
                           placeholder="Buscar ubicación...">

                    <div class="sched-locations-list" data-locations-list>
                        @foreach($inventories as $inventory)
                            @php
                                $label = ($inventory->group?->name ? $inventory->group->name . ' · ' : '') . $inventory->name;
                            @endphp
                            <label class="sched-location-option"
                                   data-location-search="{{ Str::lower($label) }}">
                                <input type="checkbox" name="inventory_ids[]" value="{{ $inventory->id }}">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <p class="sched-locations-hint" data-locations-count>
                        Ninguna ubicación seleccionada.
                    </p>
                @endif
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">
                    {{ $isCreate ? 'Crear programación' : 'Guardar cambios' }}
                </button>
            </div>
        </form>
    </div>
</div>
