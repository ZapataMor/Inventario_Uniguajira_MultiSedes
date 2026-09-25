@props([
    'mode' => 'create',
    'inventories' => collect(),
])

{{--
    Solicitud de servicio (formato RA-F-33) diligenciada por etapas:
      1. Información del solicitante.
      2. Tipo de servicio solicitado (varios a la vez, más "Otros").
      3. Descripción de la actividad y localización (bloque → salones).

    El formulario lleva `novalidate`: cada etapa se valida al avanzar desde
    schedules.js, porque el navegador no puede señalar campos de etapas ocultas.
--}}
@php
    use App\Models\InventorySchedule;

    $isCreate = $mode === 'create';
    $id = $isCreate ? 'modalCrearProgramacion' : 'modalEditarProgramacion';
    $formId = $isCreate ? 'formCrearProgramacion' : 'formEditarProgramacion';
    $prefix = $isCreate ? 'crearProgramacion' : 'editarProgramacion';
    $action = $isCreate ? url('/api/schedules/create') : url('/api/schedules/update');

    // Solo se ofrecen los bloques que tienen al menos un salón registrado.
    $blocks = $inventories
        ->filter(fn ($inventory) => $inventory->group)
        ->groupBy('group_id')
        ->map(fn ($rooms) => $rooms->first()->group)
        ->sortBy('name');

    $stages = [
        1 => 'Solicitante',
        2 => 'Tipo de servicio',
        3 => 'Actividad',
    ];
@endphp

<div id="{{ $id }}" class="modal" data-reset-on-close-without-save="true">
    <div class="modal-content modal-content-large">
        <span class="close" onclick="ocultarModal('#{{ $id }}')">&times;</span>
        <h2>{{ $isCreate ? 'Nuevo mantenimiento' : 'Editar mantenimiento' }}</h2>

        <form id="{{ $formId }}" class="sched-wizard" action="{{ $action }}" method="POST"
              autocomplete="off" novalidate data-schedule-wizard>
            @csrf
            @unless($isCreate)
                <input type="hidden" id="{{ $prefix }}Id" name="id">
            @endunless

            <ol class="sched-steps" data-stage-steps>
                @foreach($stages as $number => $label)
                    <li class="sched-step{{ $number === 1 ? ' is-active' : '' }}" data-stage-step="{{ $number }}">
                        <span class="sched-step-number">{{ $number }}</span>
                        <span class="sched-step-label">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>

            {{-- ─── Etapa 1: información del solicitante ─────────────── --}}
            <section class="sched-stage" data-stage="1">
                <h3 class="sched-stage-title">Información del solicitante</h3>

                <div class="sched-field">
                    <label for="{{ $prefix }}RequesterName">Nombre <span class="sched-req">*</span></label>
                    <input type="text" id="{{ $prefix }}RequesterName" name="requester_name"
                           maxlength="120" required placeholder="Nombre completo del solicitante">
                </div>

                <div class="sched-field-row">
                    <div class="sched-field">
                        <label for="{{ $prefix }}RequesterPosition">Cargo <span class="sched-req">*</span></label>
                        <input type="text" id="{{ $prefix }}RequesterPosition" name="requester_position"
                               maxlength="120" required placeholder="Ej: Docente, Coordinador">
                    </div>

                    <div class="sched-field">
                        <label for="{{ $prefix }}RequesterDependency">Dependencia <span class="sched-req">*</span></label>
                        <input type="text" id="{{ $prefix }}RequesterDependency" name="requester_dependency"
                               maxlength="120" required placeholder="Ej: Facultad de Ingeniería">
                    </div>
                </div>

                <div class="sched-field-row">
                    <div class="sched-field">
                        <label for="{{ $prefix }}FilingNumber">N.º de radicación</label>
                        <input type="text" id="{{ $prefix }}FilingNumber" name="filing_number"
                               maxlength="30" placeholder="Opcional">
                    </div>

                    <div class="sched-field">
                        <label for="{{ $prefix }}RequestedAt">Fecha <span class="sched-req">*</span></label>
                        <input type="date" id="{{ $prefix }}RequestedAt" name="requested_at" required
                               data-default-today>
                    </div>
                </div>
            </section>

            {{-- ─── Etapa 2: tipo de servicio solicitado ─────────────── --}}
            <section class="sched-stage" data-stage="2" hidden>
                <h3 class="sched-stage-title">Tipo de servicio solicitado</h3>
                <p class="sched-stage-hint">Marca todos los servicios que se requieren.</p>

                <div class="sched-choice-grid" data-service-options>
                    @foreach(InventorySchedule::SERVICE_TYPES as $value => $label)
                        <label class="sched-choice">
                            <input type="checkbox" name="service_types[]" value="{{ $value }}">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach

                    <label class="sched-choice">
                        <input type="checkbox" name="service_other_enabled" value="1" data-service-other-toggle>
                        <span>Otros</span>
                    </label>
                </div>

                <div class="sched-field" data-service-other-field hidden>
                    <label for="{{ $prefix }}ServiceOther">¿Qué otro servicio? <span class="sched-req">*</span></label>
                    <input type="text" id="{{ $prefix }}ServiceOther" name="service_other"
                           maxlength="150" disabled placeholder="Describe el servicio requerido">
                </div>

                <p class="sched-stage-error" data-stage-error hidden></p>
            </section>

            {{-- ─── Etapa 3: descripción de la actividad ─────────────── --}}
            <section class="sched-stage" data-stage="3" hidden>
                <h3 class="sched-stage-title">Descripción de la actividad</h3>

                <div class="sched-field">
                    <span class="sched-field-label">Nombre de la actividad <span class="sched-req">*</span></span>
                    <div class="sched-choice-row">
                        @foreach(InventorySchedule::ACTIVITY_TYPES as $value => $label)
                            <label class="sched-choice">
                                <input type="radio" name="activity_type" value="{{ $value }}" required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="sched-field">
                    <span class="sched-field-label">Tipo de mantenimiento <span class="sched-req">*</span></span>
                    <div class="sched-choice-row">
                        @foreach(InventorySchedule::MAINTENANCE_TYPES as $value => $label)
                            <label class="sched-choice">
                                <input type="radio" name="maintenance_type" value="{{ $value }}" required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="sched-field">
                    <label for="{{ $prefix }}Block">Localización <span class="sched-req">*</span></label>

                    @if($blocks->isEmpty())
                        <p class="sched-locations-empty">
                            Todavía no hay bloques con salones registrados en esta sede.
                        </p>
                    @else
                        <select id="{{ $prefix }}Block" name="group_id" required data-block-select>
                            <option value="">Selecciona un bloque</option>
                            @foreach($blocks as $block)
                                <option value="{{ $block->id }}">{{ $block->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                @if($blocks->isNotEmpty())
                    {{-- Los salones de todos los bloques se imprimen y se filtran en el cliente. --}}
                    <div class="sched-field sched-locations-field" data-rooms-field hidden>
                        <span class="sched-field-label">Salones o salas del bloque <span class="sched-req">*</span></span>

                        <input type="text" class="sched-locations-search"
                               data-locations-search
                               placeholder="Buscar salón...">

                        <div class="sched-locations-list" data-locations-list>
                            @foreach($inventories as $inventory)
                                @continue(! $inventory->group)
                                <label class="sched-location-option"
                                       data-group-id="{{ $inventory->group_id }}"
                                       data-location-search="{{ Str::lower($inventory->name) }}"
                                       hidden>
                                    <input type="checkbox" name="inventory_ids[]" value="{{ $inventory->id }}">
                                    <span>{{ $inventory->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <p class="sched-locations-hint" data-locations-count>
                            Ningún salón seleccionado.
                        </p>
                    </div>
                @endif

                <p class="sched-stage-error" data-stage-error hidden></p>
            </section>

            <div class="sched-stage-nav">
                <button type="button" class="sched-btn" data-stage-prev hidden>
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>

                <button type="button" class="sched-btn sched-btn-primary" data-stage-next>
                    Siguiente <i class="fas fa-arrow-right"></i>
                </button>

                <button type="submit" class="sched-btn sched-btn-primary" data-stage-submit hidden>
                    <i class="fas fa-check"></i>
                    {{ $isCreate ? 'Crear mantenimiento' : 'Guardar cambios' }}
                </button>
            </div>
        </form>
    </div>
</div>
