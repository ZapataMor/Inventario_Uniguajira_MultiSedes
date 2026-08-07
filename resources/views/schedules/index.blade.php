@extends('layouts.app')

@section('title', 'Programación de inventarios')

@section('content')
@php
    $currentUser = Auth::user();
    $canManageSchedules = $currentUser && ($currentUser->isAdministrator() || $currentUser->isSuperAdmin());
@endphp

<div class="container content">
    <h1>Programación de inventarios</h1>
    <p class="sched-intro">
        Cada programación genera su propio código QR y enlace público. Quien lo escanee podrá
        documentar la labor realizada sin iniciar sesión en el aplicativo.
    </p>

    <div id="schedules-topbar">
        <x-generals.top-bar
            id="searchSchedule"
            placeholder="Buscar programación por nombre"
            canCreate="false"
        >
            @if($canManageSchedules)
                <button type="button" class="create-btn" onclick="btnCrearProgramacion()">
                    Crear
                </button>
            @endif
        </x-generals.top-bar>
    </div>

    <div id="schedulesGrid" class="sched-grid">
        @forelse($schedules as $schedule)
            @php
                $publicUrl = $schedule->publicUrl();
            @endphp
            <article
                class="sched-card"
                data-schedule-card
                data-id="{{ $schedule->id }}"
                data-code="{{ $schedule->code }}"
                data-title="{{ $schedule->title }}"
                data-open="{{ $schedule->is_open ? '1' : '0' }}"
                data-url="{{ $publicUrl }}"
                data-inventory-id="{{ $schedule->inventory_id }}"
                data-search="{{ Str::lower($schedule->title . ' ' . $schedule->location_label) }}"
            >
                {{--
                    Encabezado fijo: nombre y ubicacion siempre impresos en la tarjeta.
                    Se usa <div> y no <header>: navbar.css aplica `position: fixed`
                    al elemento <header> suelto y sacaba este bloque de la tarjeta.
                --}}
                <div class="sched-card-head">
                    <h2 class="sched-card-title">{{ $schedule->title }}</h2>

                    <p class="sched-card-location">
                        <i class="fas fa-location-dot"></i>
                        @if($schedule->location_label)
                            {{ $schedule->location_label }}
                        @else
                            <span class="sched-card-location-empty">Sin ubicación asignada</span>
                        @endif
                    </p>
                </div>

                @if($publicUrl)
                    <div class="sched-share">
                        <div class="sched-qr" data-qr-target data-url="{{ $publicUrl }}">
                            <span class="sched-qr-loading">Generando QR...</span>
                        </div>

                        <div class="sched-share-info">
                            <label for="scheduleLink{{ $schedule->id }}">Enlace público</label>
                            <div class="sched-link-row">
                                <input type="text" id="scheduleLink{{ $schedule->id }}"
                                       value="{{ $publicUrl }}" readonly
                                       onfocus="this.select()">
                                <button type="button" class="sched-btn sched-btn-primary"
                                        data-action="copy" title="Copiar enlace">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>

                            <p class="sched-share-hint">
                                Escanea el código o comparte el enlace para registrar la labor.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="sched-card-foot">
                    <span class="sched-entries-count">
                        <i class="fas fa-file-signature"></i>
                        {{ $schedule->entries_count }} {{ $schedule->entries_count === 1 ? 'registro' : 'registros' }}
                    </span>

                    <span class="sched-open-flag sched-open-flag-{{ $schedule->is_open ? 'on' : 'off' }}">
                        <i class="fas {{ $schedule->is_open ? 'fa-lock-open' : 'fa-lock' }}"></i>
                        {{ $schedule->is_open ? 'Formulario abierto' : 'Formulario cerrado' }}
                    </span>
                </div>

                <div class="sched-actions">
                    <button type="button" class="sched-btn" data-action="entries">
                        <i class="fas fa-list-check"></i> Ver registros
                    </button>

                    @if($publicUrl)
                        <a class="sched-btn" href="{{ $publicUrl }}" target="_blank" rel="noopener" title="Abrir formulario">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>
                        <button type="button" class="sched-btn" data-action="download" title="Descargar QR">
                            <i class="fas fa-download"></i>
                        </button>
                        <button type="button" class="sched-btn" data-action="print" title="Imprimir QR">
                            <i class="fas fa-print"></i>
                        </button>
                    @endif

                    @if($canManageSchedules)
                        <button type="button" class="sched-btn" data-action="toggle"
                                title="{{ $schedule->is_open ? 'Cerrar formulario' : 'Reabrir formulario' }}">
                            <i class="fas {{ $schedule->is_open ? 'fa-lock' : 'fa-lock-open' }}"></i>
                        </button>
                        <button type="button" class="sched-btn" data-action="edit" title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="sched-btn sched-btn-danger" data-action="delete" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="sched-empty" data-schedule-empty>
                <i class="fas fa-calendar-plus fa-3x"></i>
                <p>Todavía no hay programaciones registradas.</p>
            </div>
        @endforelse
    </div>

    <div class="sched-empty hidden" data-schedule-no-results>
        <i class="fas fa-filter-circle-xmark fa-3x"></i>
        <p>Ninguna programación coincide con la búsqueda.</p>
    </div>

    @if($canManageSchedules)
        <x-modal.schedules.schedule mode="create" :inventories="$inventories" />
        <x-modal.schedules.schedule mode="edit" :inventories="$inventories" />
    @endif

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
