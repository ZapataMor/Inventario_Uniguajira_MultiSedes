{{--
    Resumen de la labor, tal como lo ve la persona externa apenas termina
    de diligenciar el formulario (o si vuelve a abrir el enlace).

    El documento descargable es el formato RA-F-33 diligenciado, que arma
    ScheduleReceiptService sobre la plantilla original.
--}}
<section class="receipt">
    <div class="receipt-head">
        <div>
            <p class="receipt-kicker">Reporte del servicio</p>
            <p class="receipt-code">RA-F-33</p>
        </div>
        <span class="receipt-sede">
            <i class="fas fa-building-columns"></i> {{ $sedeLabel }}
        </span>
    </div>

    <dl class="receipt-data">
        <div>
            <dt>Actividad realizada por</dt>
            <dd>{{ $entry->performed_by ?: '—' }}</dd>
        </div>
        @if($entry->equipment_label)
            <div>
                <dt>Equipo</dt>
                <dd>{{ $entry->equipment_label }}</dd>
            </div>
        @endif
        <div>
            <dt>Inicio</dt>
            <dd>{{ $entry->started_at?->format('d/m/Y H:i') }}</dd>
        </div>
        <div>
            <dt>Finalización</dt>
            <dd>{{ $entry->finished_at?->format('d/m/Y H:i') }}</dd>
        </div>
        <div>
            <dt>Duración</dt>
            <dd>{{ $entry->duration_label }}</dd>
        </div>
        <div>
            <dt>Registro recibido</dt>
            <dd>{{ $entry->registeredAtLabel($timezone) }}</dd>
        </div>
    </dl>

    @if($entry->action)
        <div class="receipt-note">
            <p class="receipt-note-label">Acción</p>
            <p class="receipt-note-list">{{ $entry->action }}</p>
        </div>
    @endif

    @if($entry->materials)
        <div class="receipt-note">
            <p class="receipt-note-label">Materiales</p>
            <p class="receipt-note-list">{{ $entry->materials }}</p>
        </div>
    @endif

    @if($entry->images->isNotEmpty())
        <div class="receipt-shots">
            <p class="receipt-shots-label">
                Evidencias fotográficas ({{ $entry->images->count() }})
            </p>

            <ul class="shot-grid">
                @foreach($entry->images as $index => $image)
                    @php
                        $imageUrl = route('schedules.public.image', $routeParams + ['imageId' => $image->id]);
                    @endphp
                    <li class="shot-card">
                        <button type="button" class="shot-open"
                                data-viewer-open
                                data-src="{{ $imageUrl }}"
                                data-caption="{{ $image->description }}">
                            <img src="{{ $imageUrl }}" alt="Evidencia {{ $index + 1 }}" loading="lazy">
                            <span class="shot-zoom"><i class="fas fa-magnifying-glass-plus"></i></span>
                        </button>

                        @if($image->description)
                            <p class="shot-caption">{{ $image->description }}</p>
                        @else
                            <p class="shot-caption shot-caption-empty">Sin descripción</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <a class="btn btn-primary receipt-download"
       href="{{ route('schedules.public.receipt', $routeParams) }}">
        <i class="fas fa-file-arrow-down"></i> Descargar formato RA-F-33 (PDF)
    </a>

    <p class="receipt-hint">
        Guarda este formato como constancia del servicio realizado.
    </p>
</section>
