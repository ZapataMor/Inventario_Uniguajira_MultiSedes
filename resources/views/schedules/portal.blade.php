@extends('layouts.app')

@section('title', 'Mantenimientos realizados')

@section('content')
@php
    $schedulesBySede = $schedulesBySede ?? collect();
@endphp

{{--
    Catalogo del portal central: mantenimientos ya diligenciados de cada sede,
    en modo solo lectura. Crear o gestionar programaciones se hace dentro de la sede.
--}}
<div class="container content" id="schedulesPortal">
    <h1>Mantenimientos realizados</h1>
    <p class="sched-intro">
        Labores de mantenimiento ya documentadas en cada sede. Haz clic en una tarjeta para ver
        el detalle y las evidencias, o ábrela en su sede para descargar el formato RA-F-33.
    </p>

    <div id="schedules-topbar">
        <x-generals.top-bar
            id="searchSchedule"
            placeholder="Buscar por nombre, solicitante, dependencia o ubicación"
            canCreate="false"
        />
    </div>

    <div class="inventory-sede-list">
        @foreach($schedulesBySede as $sedeData)
            <details class="inventory-sede-dropdown" data-schedule-sede-dropdown>
                <summary class="inventory-sede-summary">
                    <span class="inventory-sede-title">{{ $sedeData['dropdown_label'] }}</span>
                    <span class="inventory-sede-count">
                        <span data-visible-count>{{ $sedeData['schedules']->count() }}</span> mantenimientos
                    </span>
                </summary>

                <div class="inventory-sede-body">
                    @if($sedeData['schedules']->isEmpty())
                        <p class="inventory-sede-empty">No hay mantenimientos realizados en esta sede.</p>
                    @else
                        <div class="sched-grid">
                            @foreach($sedeData['schedules'] as $schedule)
                                @php
                                    $entry = $schedule->entry;
                                    $locations = $schedule->location_labels;
                                    $images = $entry?->images ?? collect();
                                    $imageParams = ['tenant' => $sedeData['tenant_slug'], 'portal' => 1];
                                @endphp
                                <article
                                    class="sched-card sched-card-done sched-card-clickable"
                                    data-schedule-card
                                    data-id="{{ $schedule->id }}"
                                    data-title="{{ $schedule->title }}"
                                    data-completed="1"
                                    data-tenant="{{ $sedeData['tenant_slug'] }}"
                                    data-search="{{ Str::lower(implode(' ', [
                                        $schedule->title,
                                        implode(' ', $locations),
                                        $schedule->requester_name,
                                        $schedule->requester_dependency,
                                        $schedule->filing_number,
                                        $entry->performed_by ?? '',
                                        $entry->equipment_name ?? '',
                                    ])) }}"
                                >
                                    {{-- <div> y no <header>: navbar.css fija el elemento <header> suelto. --}}
                                    <div class="sched-card-head">
                                        <h2 class="sched-card-title">{{ $schedule->title }}</h2>

                                        @if($schedule->requester_name)
                                            <p class="sched-card-request">
                                                Solicitado por <strong>{{ $schedule->requester_name }}</strong>
                                                ({{ $schedule->requester_position }}, {{ $schedule->requester_dependency }})
                                                @if($schedule->requested_at)
                                                    · {{ $schedule->requested_at->format('d/m/Y') }}
                                                @endif
                                                @if($schedule->filing_number)
                                                    · Rad. {{ $schedule->filing_number }}
                                                @endif
                                            </p>
                                        @endif

                                        @if($schedule->activity_label || $schedule->service_labels)
                                            <ul class="sched-card-tags">
                                                @if($schedule->activity_label)
                                                    <li class="sched-tag-main">
                                                        {{ $schedule->activity_label }}{{ $schedule->maintenance_type_label ? ' · ' . $schedule->maintenance_type_label : '' }}
                                                    </li>
                                                @endif
                                                @foreach($schedule->service_labels as $service)
                                                    <li>{{ $service }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @if($locations)
                                            <ul class="sched-card-locations">
                                                @foreach($locations as $location)
                                                    <li>
                                                        <i class="fas fa-location-dot"></i>
                                                        <span>{{ $location }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="sched-card-location">
                                                <i class="fas fa-location-dot"></i>
                                                <span class="sched-card-location-empty">Sin ubicación asignada</span>
                                            </p>
                                        @endif
                                    </div>

                                    <div class="sched-record">
                                        <div class="sched-record-head">
                                            <span class="sched-record-badge">
                                                <i class="fas fa-circle-check"></i> Diligenciada
                                            </span>
                                            <span class="sched-record-duration">{{ $entry->duration_label }}</span>
                                        </div>

                                        <h3 class="sched-record-name">Acción realizada</h3>

                                        @if($entry->action)
                                            <p class="sched-record-description">{{ Str::limit($entry->action, 220) }}</p>
                                        @endif

                                        <ul class="sched-record-meta">
                                            <li><i class="fas fa-user"></i> Realizada por: {{ $entry->performed_by ?: '—' }}</li>
                                            @if($entry->equipment_label)
                                                <li><i class="fas fa-screwdriver-wrench"></i> {{ $entry->equipment_label }}</li>
                                            @endif
                                            <li><i class="fas fa-play"></i> Inicio: {{ $entry->started_at?->format('d/m/Y H:i') }}</li>
                                            <li><i class="fas fa-flag-checkered"></i> Fin: {{ $entry->finished_at?->format('d/m/Y H:i') }}</li>
                                            <li><i class="fas fa-clock"></i> Registrado: {{ $entry->registeredAtLabel($sedeData['timezone']) }}</li>
                                        </ul>

                                        @if($images->isNotEmpty())
                                            <div class="sched-record-shots">
                                                @foreach($images->take(4) as $image)
                                                    <img src="{{ route('schedules.image', ['imageId' => $image->id] + $imageParams) }}"
                                                         alt="{{ $image->description ?: 'Evidencia fotográfica' }}"
                                                         loading="lazy">
                                                @endforeach

                                                @if($images->count() > 4)
                                                    <span class="sched-record-shots-more">+{{ $images->count() - 4 }}</span>
                                                @endif

                                                <span class="sched-record-shots-label">
                                                    <i class="fas fa-images"></i>
                                                    {{ $images->count() }} {{ $images->count() === 1 ? 'imagen' : 'imágenes' }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="sched-actions">
                                        <button type="button" class="sched-btn" data-action="entries">
                                            <i class="fas fa-list-check"></i> Ver registros
                                        </button>

                                        <button
                                            type="button"
                                            class="sched-btn sched-btn-primary"
                                            title="Abrir la programación de la sede para descargar el formato RA-F-33"
                                            onclick="loadContent('{{ route('portal.switch', ['slug' => $sedeData['tenant_slug'], 'redirect' => '/schedules', 'inplace' => 1]) }}', { updateHistory: false, onSuccess: () => initSchedulesModule() })"
                                        >
                                            <i class="fas fa-external-link-alt"></i> Abrir en la sede
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <p class="inventory-sede-filter-empty hidden" data-sede-empty>
                            No hay resultados para esta sede con el filtro actual.
                        </p>
                    @endif
                </div>
            </details>
        @endforeach
    </div>

    <x-modal.schedules.entries />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initSchedulesModule === 'function') {
                    initSchedulesModule();
                }
            });
        </script>
    @endonce
</div>
@endsection
