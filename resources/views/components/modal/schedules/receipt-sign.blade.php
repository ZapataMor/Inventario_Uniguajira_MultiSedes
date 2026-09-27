@props([
    'savedSignature' => null,
])

{{--
    Vista previa y descarga firmada del formato RA-F-33.

    A la izquierda se ve el formato tal como quedó diligenciado (sin la
    firma de quien recibe). Para descargarlo hay que firmar en
    "Actividad recibida por": dibujando en el recuadro o, si el usuario ya
    guardó su firma, marcando "Firmar con firma guardada".
--}}
<div id="modalFirmarFormato" class="modal">
    <div class="modal-content sched-sign-modal">
        <span class="close" onclick="ocultarModal('#modalFirmarFormato')">&times;</span>
        <h2>Formato RA-F-33</h2>
        <p class="sched-modal-title" data-sign-title></p>

        <div class="sched-sign-layout">
            <div class="sched-sign-preview" data-sign-preview>
                <p class="sched-sign-preview-status" data-sign-preview-status>Cargando vista previa...</p>
            </div>

            <form class="sched-sign-panel" data-sign-form novalidate>
                <h3 class="sched-sign-heading">Firma de quien recibe la actividad</h3>
                <p class="sched-sign-hint">
                    Tu nombre, <strong>{{ Auth::user()?->name }}</strong>, irá en
                    "Actividad recibida por" y tu firma sobre su línea.
                </p>

                <div class="sched-sign-saved" data-sign-saved-block @if(! $savedSignature) hidden @endif>
                    <label class="sched-sign-check">
                        <input type="checkbox" data-sign-use-saved>
                        <span>Firmar con firma guardada</span>
                    </label>

                    <div class="sched-sign-saved-preview" data-sign-saved-preview hidden>
                        <img src="{{ $savedSignature }}" alt="Tu firma guardada" data-sign-saved-image>
                    </div>

                    <button type="button" class="sched-sign-link" data-sign-forget>
                        Eliminar firma guardada
                    </button>
                </div>

                <div class="sched-sign-draw" data-sign-draw>
                    <p class="sched-sign-label">Firma con el dedo o con el mouse:</p>

                    <div class="sched-sign-box">
                        <canvas class="sched-sign-canvas" data-sign-canvas aria-label="Recuadro para firmar"></canvas>
                        <span class="sched-sign-placeholder" data-sign-placeholder>Firma aquí</span>
                        <span class="sched-sign-baseline" aria-hidden="true"></span>
                    </div>

                    <div class="sched-sign-draw-actions">
                        <label class="sched-sign-check">
                            <input type="checkbox" data-sign-save>
                            <span>Guardar esta firma para próximas descargas</span>
                        </label>

                        <button type="button" class="sched-btn" data-sign-clear>
                            <i class="fas fa-eraser"></i> Borrar
                        </button>
                    </div>
                </div>

                <p class="sched-stage-error" data-sign-error hidden></p>

                <button type="submit" class="sched-btn sched-btn-primary sched-sign-submit" data-sign-submit disabled>
                    <i class="fas fa-file-signature"></i> Firmar y descargar
                </button>
            </form>
        </div>
    </div>
</div>
