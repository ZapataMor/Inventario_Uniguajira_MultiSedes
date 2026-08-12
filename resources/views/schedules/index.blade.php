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
        Cada programación genera su propio código QR y enlace público, y se diligencia una sola vez.
        Cuando la persona externa envía el formulario, el QR desaparece de la tarjeta y en su lugar
        queda la información registrada. Para una nueva labor, crea otra programación.
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
                // Diligenciada = QR consumido: se muestra lo registrado, no el QR.
                $entry = $schedule->entry;
                $isCompleted = $entry !== null;
                $publicUrl = $isCompleted ? null : $schedule->publicUrl();
                $locations = $schedule->location_labels;
                $images = $entry?->images ?? collect();
            @endphp
            <article
                class="sched-card{{ $isCompleted ? ' sched-card-done sched-card-clickable' : '' }}"
                data-schedule-card
                data-id="{{ $schedule->id }}"
                data-code="{{ $schedule->code }}"
                data-title="{{ $schedule->title }}"
                data-open="{{ $schedule->is_open ? '1' : '0' }}"
                data-completed="{{ $isCompleted ? '1' : '0' }}"
                data-url="{{ $publicUrl }}"
                data-inventory-ids="{{ implode(',', $schedule->inventories->pluck('id')->all()) }}"
                data-search="{{ Str::lower($schedule->title . ' ' . implode(' ', $locations) . ' ' . ($entry->work_name ?? '') . ' ' . ($entry->responsible_name ?? '')) }}"
            >
                {{--
                    Encabezado fijo: nombre y ubicaciones siempre impresos en la tarjeta.
                    Se usa <div> y no <header>: navbar.css aplica `position: fixed`
                    al elemento <header> suelto y sacaba este bloque de la tarjeta.
                --}}
                <div class="sched-card-head">
                    <h2 class="sched-card-title">{{ $schedule->title }}</h2>

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

                @if($isCompleted)
                    {{-- El QR y el enlace se sustituyen por lo que documentó la persona externa. --}}
                    <div class="sched-record">
                        <div class="sched-record-head">
                            <span class="sched-record-badge">
                                <i class="fas fa-circle-check"></i> Diligenciada
                            </span>
                            <span class="sched-record-duration">{{ $entry->duration_label }}</span>
                        </div>

                        <h3 class="sched-record-name">{{ $entry->work_name }}</h3>

                        @if($entry->description)
                            <p class="sched-record-description">{{ $entry->description }}</p>
                        @endif

                        <ul class="sched-record-meta">
                            <li><i class="fas fa-user"></i> {{ $entry->responsible_name }}</li>
                            <li><i class="fas fa-play"></i> Inicio: {{ $entry->started_at?->format('d/m/Y H:i') }}</li>
                            <li><i class="fas fa-flag-checkered"></i> Fin: {{ $entry->finished_at?->format('d/m/Y H:i') }}</li>
                            <li><i class="fas fa-clock"></i> Registrado: {{ $entry->registeredAtLabel($timezone) }}</li>
                        </ul>

                        {{--
                            Adelanto de las evidencias: la tarjeta insinúa que hay
                            fotos y el detalle completo se abre al hacer clic en ella.
                        --}}
                        @if($images->isNotEmpty())
                            <div class="sched-record-shots">
                                @foreach($images->take(4) as $image)
                                    <img src="{{ route('schedules.image', $image->id) }}"
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
                @elseif($publicUrl)
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
                                Solo puede usarse una vez.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="sched-card-foot">
                    <span class="sched-entries-count">
                        <i class="fas fa-file-signature"></i>
                        {{ $schedule->entries_count }} {{ $schedule->entries_count === 1 ? 'registro' : 'registros' }}
                    </span>

                    @if($isCompleted)
                        <span class="sched-open-flag sched-open-flag-done">
                            <i class="fas fa-circle-check"></i>
                            Labor documentada
                        </span>
                    @else
                        <span class="sched-open-flag sched-open-flag-{{ $schedule->is_open ? 'on' : 'off' }}">
                            <i class="fas {{ $schedule->is_open ? 'fa-lock-open' : 'fa-lock' }}"></i>
                            {{ $schedule->is_open ? 'Formulario abierto' : 'Formulario cerrado' }}
                        </span>
                    @endif
                </div>

                <div class="sched-actions">
                    @if($schedule->entries_count > 0)
                        <button type="button" class="sched-btn" data-action="entries">
                            <i class="fas fa-list-check"></i> Ver registros
                        </button>
                    @endif

                    {{-- El comprobante solo existe cuando el formulario ya fue diligenciado. --}}
                    @if($isCompleted)
                        <a class="sched-btn sched-btn-primary"
                           href="{{ route('schedules.receipt', $schedule->id) }}"
                           title="Descargar comprobante en PDF">
                            <i class="fas fa-file-arrow-down"></i> Comprobante
                        </a>
                    @endif

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
                        {{-- Una programación diligenciada es histórico: solo se puede eliminar. --}}
                        @unless($isCompleted)
                            <button type="button" class="sched-btn" data-action="toggle"
                                    title="{{ $schedule->is_open ? 'Cerrar formulario' : 'Reabrir formulario' }}">
                                <i class="fas {{ $schedule->is_open ? 'fa-lock' : 'fa-lock-open' }}"></i>
                            </button>
                            <button type="button" class="sched-btn" data-action="edit" title="Editar">
                                <i class="fas fa-pen"></i>
                            </button>
                        @endunless

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
