{{--
    Comprobante de la labor, tal como lo ve la persona externa apenas
    termina de diligenciar el formulario (o si vuelve a abrir el enlace).

    Es un resumen en pantalla del mismo documento que entrega el botón de
    descarga: la fuente de verdad del PDF es `schedules/pdf/comprobante`.
--}}
<section class="receipt">
    <div class="receipt-head">
        <div>
            <p class="receipt-kicker">Comprobante de labor realizada</p>
            <p class="receipt-code">{{ $entry->receipt_code }}</p>
        </div>
        <span class="receipt-sede">
            <i class="fas fa-building-columns"></i> {{ $sedeLabel }}
        </span>
    </div>

    <dl class="receipt-data">
        <div>
            <dt>Trabajo realizado</dt>
            <dd>{{ $entry->work_name }}</dd>
        </div>
        <div>
            <dt>Responsable</dt>
            <dd>{{ $entry->responsible_name }}</dd>
        </div>
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

    @if($entry->description)
        <div class="receipt-note">
            <p class="receipt-note-label">Observaciones</p>
            <p>{{ $entry->description }}</p>
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
        <i class="fas fa-file-arrow-down"></i> Descargar comprobante en PDF
    </a>

    <p class="receipt-hint">
        Guarda este comprobante como constancia del trabajo realizado.
    </p>
</section>
