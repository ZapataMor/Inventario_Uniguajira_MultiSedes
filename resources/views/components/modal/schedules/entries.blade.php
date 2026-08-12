{{--
    Detalle de las labores documentadas de una programación.

    Se abre desde el botón "Ver registros" y también al hacer clic en la
    tarjeta de una programación ya diligenciada, que es donde se consultan
    las evidencias fotográficas.
--}}
<div id="modalProgramacionRegistros" class="modal">
    <div class="modal-content modal-content-large">
        <span class="close" onclick="ocultarModal('#modalProgramacionRegistros')">&times;</span>
        <h2>Labores documentadas</h2>

        <div class="sched-modal-head">
            <p class="sched-modal-title" data-entries-title></p>

            <a class="sched-btn sched-btn-primary sched-modal-receipt hidden"
               href="#" data-entries-receipt>
                <i class="fas fa-file-arrow-down"></i> Descargar comprobante
            </a>
        </div>

        <div class="sched-entries" data-entries-body>
            <p class="sched-entries-loading">Cargando registros...</p>
        </div>
    </div>
</div>

{{-- Visor a pantalla completa de las evidencias --}}
<div class="sched-viewer" data-sched-viewer hidden>
    <button type="button" class="sched-viewer-close" data-sched-viewer-close aria-label="Cerrar">
        <i class="fas fa-xmark"></i>
    </button>
    <figure class="sched-viewer-figure">
        <img src="" alt="" data-sched-viewer-image>
        <figcaption data-sched-viewer-caption></figcaption>
    </figure>
</div>
