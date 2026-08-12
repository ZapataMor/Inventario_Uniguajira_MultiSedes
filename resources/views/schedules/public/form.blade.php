@php
    $appName = $branding?->app_name ?: config('app.name', 'Inventario Uniguajira');
    $sedeName = $branding?->sede_name ?: $tenant->name;
    $institution = $branding?->institution_name ?: 'Universidad de La Guajira';
    $favicon = $branding?->favicon ?: 'assets/images/favicon-uniguajira-32x32.webp';
    $assetVersion = static function (string $path): int {
        $fullPath = public_path($path);
        $modifiedAt = is_file($fullPath) ? filemtime($fullPath) : false;

        return $modifiedAt ?: time();
    };
    $routeParams = ['tenantSlug' => $tenant->slug, 'code' => $schedule->code];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $schedule->title }} · {{ $appName }}</title>
    <link rel="icon" href="{{ asset($favicon) }}" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/schedule-public.css') }}?v={{ $assetVersion('assets/css/schedule-public.css') }}">
</head>
<body>

{{-- Animación de inicio del aplicativo, igual a la del acceso --}}
<div class="splash" data-splash data-state="on" aria-hidden="true">
    <div class="splash-glow"></div>
    <div class="splash-logo-box" data-splash-box>
        <div class="splash-ring"></div>
        <img src="{{ asset('images/sofia-transparent.png') }}" alt="" class="splash-logo">
        <div class="splash-sweep" data-splash-sweep></div>
    </div>
    <div class="splash-bar"><span></span></div>
    <p class="splash-caption">{{ $sedeName }}</p>
</div>

<main class="page" data-page>
    <header class="page-head">
        <p class="page-institution">{{ $institution }}</p>
        <h1 class="page-title">{{ $appName }}</h1>
        <p class="page-sede">{{ $sedeName }}</p>
    </header>

    <section class="card">
        <div class="card-head">
            <span class="badge">Programación</span>
            <h2 class="card-title">{{ $schedule->title }}</h2>

            @if($schedule->location_labels)
                <ul class="card-meta">
                    @foreach($schedule->location_labels as $location)
                        <li>
                            <i class="fas fa-location-dot"></i>
                            <span>{{ $location }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if($entry)
            {{--
                El formulario es de un solo uso: una vez diligenciado, en su
                lugar queda el comprobante de la labor. Se muestra igual si la
                persona acaba de enviarlo o si vuelve a abrir el enlace más
                tarde, para que siempre pueda descargar su constancia.
            --}}
            <div class="state state-success state-compact" data-success-state>
                <i class="fas fa-circle-check"></i>
                <h3>{{ $submitted ? '¡Registro enviado!' : 'Labor ya documentada' }}</h3>
                <p>
                    @if($submitted)
                        Gracias. La labor quedó documentada y ya es visible para el equipo de inventario.
                    @else
                        Esta programación fue diligenciada por {{ $entry->responsible_name }}.
                        Puedes volver a descargar el comprobante cuando lo necesites.
                    @endif
                </p>
                <p class="state-note">Este código QR ya fue utilizado y no admite más registros.</p>
            </div>

            @include('schedules.public.receipt', [
                'entry' => $entry,
                'sedeLabel' => \App\Services\Schedules\ScheduleReceiptService::sedeLabel($sedeName),
                'timezone' => $timezone,
                'routeParams' => $routeParams,
            ])
        @elseif(! $schedule->is_open)
            <div class="state state-closed">
                <i class="fas fa-lock"></i>
                <h3>Formulario cerrado</h3>
                <p>Esta programación ya no admite nuevos registros. Comunícate con el área de inventario.</p>
            </div>
        @else
            <form class="form" method="POST" data-schedule-form
                  data-evidence-endpoint="{{ route('schedules.public.evidence', $routeParams) }}"
                  action="{{ route('schedules.public.store', $routeParams) }}">
                @csrf

                <p class="form-lead">Cuéntanos qué trabajo realizaste.</p>

                @if(isset($errors) && $errors->any())
                    <div class="alert">
                        <i class="fas fa-triangle-exclamation"></i>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="field">
                    <label for="work_name">Nombre del trabajo realizado <span class="req">*</span></label>
                    <input type="text" id="work_name" name="work_name" maxlength="255" required
                           value="{{ old('work_name') }}"
                           placeholder="Ej: Mantenimiento a equipos de cómputo, limpieza a aires acondicionados...">
                </div>

                <div class="field">
                    <label for="responsible_name">Nombre del responsable <span class="req">*</span></label>
                    <input type="text" id="responsible_name" name="responsible_name" maxlength="255" required
                           value="{{ old('responsible_name') }}"
                           placeholder="Nombre completo de quien ejecutó la labor">
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="started_at">Fecha y hora de inicio <span class="req">*</span></label>
                        <input type="datetime-local" id="started_at" name="started_at" required
                               value="{{ old('started_at') }}">
                    </div>

                    <div class="field">
                        <label for="finished_at">Fecha y hora de finalización <span class="req">*</span></label>
                        <input type="datetime-local" id="finished_at" name="finished_at" required
                               value="{{ old('finished_at') }}">
                    </div>
                </div>

                <div class="field">
                    <label for="description">Observaciones (opcional)</label>
                    <textarea id="description" name="description" rows="4" maxlength="2000"
                              placeholder="Detalles del trabajo, materiales usados, novedades encontradas...">{{ old('description') }}</textarea>
                </div>

                {{--
                    Las fotos se suben una a una en cuanto se seleccionan, y el
                    formulario solo envía los tokens resultantes. Así se pueden
                    adjuntar todas las que hagan falta sin chocar contra los
                    límites de tamaño de petición de PHP.
                --}}
                <div class="field evidence" data-evidence>
                    <label>Evidencias fotográficas (opcional)</label>
                    <p class="field-hint">
                        Adjunta todas las fotos que necesites. Cada una puede llevar
                        una descripción, también opcional.
                    </p>

                    <input type="file" accept="image/*" multiple hidden data-evidence-input>

                    <button type="button" class="btn btn-ghost" data-evidence-add>
                        <i class="fas fa-camera"></i> Añadir imágenes
                    </button>

                    <p class="evidence-empty" data-evidence-empty>
                        Todavía no has adjuntado imágenes.
                    </p>

                    <ul class="evidence-list" data-evidence-list></ul>
                </div>

                <button type="submit" class="btn btn-primary" data-submit>
                    <i class="fas fa-paper-plane"></i> Enviar registro
                </button>
            </form>
        @endif
    </section>

    <footer class="page-foot">
        <p>{{ $institution }} · {{ $sedeName }}</p>
        <p class="page-foot-note">Formulario de documentación de labores. No requiere iniciar sesión.</p>
    </footer>
</main>

{{-- Visor a pantalla completa de las evidencias del comprobante --}}
<div class="viewer" data-viewer hidden>
    <button type="button" class="viewer-close" data-viewer-close aria-label="Cerrar">
        <i class="fas fa-xmark"></i>
    </button>
    <figure class="viewer-figure">
        <img src="" alt="" data-viewer-image>
        <figcaption data-viewer-caption></figcaption>
    </figure>
</div>

<script src="{{ asset('assets/js/schedule-public.js') }}?v={{ $assetVersion('assets/js/schedule-public.js') }}"></script>
</body>
</html>
