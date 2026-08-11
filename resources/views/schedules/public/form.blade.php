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

        @if($submitted)
            {{--
                El formulario es de un solo uso: no se ofrece registrar otra
                labor. Para eso se genera una programacion nueva con su QR.
            --}}
            <div class="state state-success" data-success-state>
                <i class="fas fa-circle-check"></i>
                <h3>¡Registro enviado!</h3>
                <p>Gracias. La labor quedó documentada y ya es visible para el equipo de inventario.</p>
                <p class="state-note">Este código QR ya fue utilizado y no admite más registros.</p>
            </div>
        @elseif($entry)
            <div class="state state-closed">
                <i class="fas fa-circle-check"></i>
                <h3>Programación ya diligenciada</h3>
                <p>
                    Esta labor fue documentada el {{ $entry->created_at?->format('d/m/Y H:i') }}
                    por {{ $entry->responsible_name }}. Cada código QR se usa una sola vez.
                </p>
            </div>
        @elseif(! $schedule->is_open)
            <div class="state state-closed">
                <i class="fas fa-lock"></i>
                <h3>Formulario cerrado</h3>
                <p>Esta programación ya no admite nuevos registros. Comunícate con el área de inventario.</p>
            </div>
        @else
            <form class="form" method="POST"
                  action="{{ route('schedules.public.store', ['tenantSlug' => $tenant->slug, 'code' => $schedule->code]) }}">
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

                <button type="submit" class="btn btn-primary">
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

<script>
    (() => {
        const splash = document.querySelector('[data-splash]');
        const box = document.querySelector('[data-splash-box]');
        const sweep = document.querySelector('[data-splash-sweep]');

        if (! splash) {
            return;
        }

        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const dismiss = () => {
            if (splash.dataset.state === 'off') {
                return;
            }

            splash.dataset.state = 'off';
            box?.setAttribute('data-state', 'exit');
            window.setTimeout(() => splash.remove(), 800);
        };

        if (reduce) {
            dismiss();
            return;
        }

        window.setTimeout(() => sweep?.setAttribute('data-active', 'true'), 700);
        window.setTimeout(dismiss, 2600);

        // Permite saltar la animación con un toque o una tecla.
        splash.addEventListener('click', dismiss);
        document.addEventListener('keydown', dismiss, { once: true });
    })();

    (() => {
        const started = document.getElementById('started_at');
        const finished = document.getElementById('finished_at');

        if (! started || ! finished) {
            return;
        }

        // La finalización nunca puede ser anterior al inicio.
        const syncMin = () => {
            finished.min = started.value || '';

            if (finished.value && started.value && finished.value < started.value) {
                finished.value = started.value;
            }
        };

        started.addEventListener('change', syncMin);
        syncMin();
    })();
</script>
</body>
</html>
