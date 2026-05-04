@extends('layouts.app')

@section('title', 'Carpetas de Reportes')

@section('content')
@php
    $isPortalReportsCatalog = $isPortalReportsCatalog ?? false;
    $foldersBySede = $foldersBySede ?? collect();
@endphp
<div class="container content">
    <h1>Carpetas</h1>

    <div id="folders-topbar">
        <x-generals.top-bar
            id="searchFolder"
            placeholder="Buscar carpeta"
            :modal="Auth::user()->isAdministrator() ? '#modalCrearCarpeta' : null"
            canCreate="{{ $isPortalReportsCatalog ? 'false' : 'true' }}"
        />
    </div>

    @if(Auth::user()->isAdministrator())
        <div id="control-bar-folder" class="control-bar">
            <div class="selected-name">1 seleccionado</div>
            <div class="control-actions">
                <button class="control-btn" title="Renombrar" onclick="btnRenombrarCarpeta()">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="control-btn" title="Eliminar" onclick="btnEliminarCarpeta()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    @endif

    <div id="folders" class="report-grid">
        @if($folders->isEmpty())
            <div class="report-empty-state">
                <i class="fas fa-folder fa-3x"></i>
                <p>No hay carpetas de reportes disponibles</p>
            </div>
        @elseif($isPortalReportsCatalog)
            <div class="inventory-sede-list">
                @foreach ($foldersBySede as $sedeData)
                    <details class="inventory-sede-dropdown" data-report-sede-dropdown>
                        <summary class="inventory-sede-summary">
                            <span class="inventory-sede-title">{{ $sedeData['dropdown_label'] }}</span>
                            <span class="inventory-sede-count">
                                <span data-visible-count>{{ $sedeData['folders']->count() }}</span> carpetas
                            </span>
                        </summary>

                        <div class="inventory-sede-body">
                            @if($sedeData['folders']->isEmpty())
                                <p class="inventory-sede-empty">No hay carpetas de reportes en esta sede.</p>
                            @else
                                @foreach($sedeData['folders'] as $folder)
                                    <div class="report-folder-card card-item">
                                        <div class="report-folder-left">
                                            <i class="fas fa-folder fa-2x report-folder-icon"></i>
                                        </div>

                                        <div class="report-folder-center">
                                            <div class="report-folder-name name-item">{{ $folder->name }}</div>
                                            <div class="report-folder-stats">
                                                <span class="report-stat-item">
                                                    <i class="fas fa-file-alt"></i>
                                                    {{ $folder->reports_count }} reportes
                                                </span>
                                            </div>
                                        </div>

                                        <div class="report-folder-right">
                                            <button
                                                class="btn-open"
                                                onclick="loadContent('{{ route('portal.switch', ['slug' => $sedeData['tenant_slug'], 'redirect' => '/reports/folder/' . $folder->id, 'inplace' => 1]) }}', { updateHistory: false, onSuccess: () => initReportsModule() })"
                                            >
                                                <i class="fas fa-external-link-alt"></i> Abrir
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                <p class="inventory-sede-filter-empty hidden" data-sede-empty>
                                    No hay resultados para esta sede con el filtro actual.
                                </p>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        @else
            @foreach($folders as $folder)
                <div
                    class="report-folder-card card-item"
                    @if(Auth::user()->isAdministrator())
                        data-id="{{ $folder->id }}"
                        data-name="{{ $folder->name }}"
                        data-type="folder"
                        onclick="toggleSelectItem(this)"
                    @endif
                >
                    <div class="report-folder-left">
                        <i class="fas fa-folder fa-2x report-folder-icon"></i>
                    </div>

                    <div class="report-folder-center">
                        <div class="report-folder-name name-item">{{ $folder->name }}</div>
                        <div class="report-folder-stats">
                            <span class="report-stat-item">
                                <i class="fas fa-file-alt"></i>
                                {{ $folder->reports_count }} reportes
                            </span>
                        </div>
                    </div>

                    <div class="report-folder-right">
                        <button class="btn-open" onclick="abrirCarpeta({{ $folder->id }})">
                            <i class="fas fa-external-link-alt"></i> Abrir
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div id="report-content" class="hidden">
        <div class="report-back-and-title">
            <span id="folder-name" class="location">Reportes</span>
            <button class="report-btn-back" onclick="cerrarCarpeta()">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
        </div>

        <x-generals.top-bar id="searchReport" placeholder="Buscar reporte" canCreate="false" />

        @if(Auth::user()->isAdministrator())
            <div class="report-options-panel">
                <div class="report-option-box">
                    <h3>Generar</h3>
                    <ul class="report-generation-list">
                        <li onclick="mostrarModalReporte('#modalCrearReporteInventario')">
                            <i class="fas fa-arrow-right"></i> reporte de un inventario
                        </li>
                        <li onclick="mostrarModalReporte('#modalCrearReporteGrupo')">
                            <i class="fas fa-arrow-right"></i> reporte de un grupo
                        </li>
                        <li onclick="mostrarModalReporte('#modalCrearReporteTodos')">
                            <i class="fas fa-arrow-right"></i> reporte de todos los inventarios
                        </li>
                        <li onclick="mostrarModalReporte('#modalCrearReporteBienes')">
                            <i class="fas fa-arrow-right"></i> reporte de bienes
                        </li>
                        <li onclick="mostrarModalReporte('#modalCrearReporteEquipos')">
                            <i class="fas fa-arrow-right"></i> reporte de equipos
                        </li>
                        <li onclick="mostrarModalReporte('#modalCrearReporteDadosDeBaja')">
                            <i class="fas fa-arrow-right"></i> reporte de dados de baja
                        </li>
                        <li onclick="mostrarModalReporte('#modalCrearReporteHistorial')">
                            <i class="fas fa-arrow-right"></i> reporte de historial
                        </li>
                    </ul>
                </div>
            </div>
        @endif

        @if(Auth::user()->isAdministrator())
            <div id="control-bar-report" class="control-bar">
                <div class="selected-name">1 seleccionado</div>
                <div class="control-actions">
                    <button class="control-btn" title="Renombrar" onclick="btnRenombrarReporte()">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="control-btn" title="Eliminar" onclick="btnEliminarReporte()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endif

        <div id="report-content-item" class="report-item-grid"></div>
    </div>

    <x-modal.report.create-folder />
    <x-modal.report.rename-folder />

    <div id="modalCrearReporteInventario" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalCrearReporteInventario')">&times;</span>
            <h2>Reporte de inventario</h2>

            <form id="formReporteDeUnInventario" action="{{ url('/api/reports/create') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="folderIdInventario" name="folder_id" />
                <input type="hidden" name="tipoReporte" value="inventario" />

                <div>
                    <label for="nombreReporte">Nombre del reporte:</label>
                    <input type="text" id="nombreReporte" name="nombreReporte" required />
                </div>

                <div>
                    <label for="grupoSeleccionado">Grupo:</label>
                    <select id="grupoSeleccionado" name="grupo_id" required>
                        <option value="">Seleccione un grupo</option>
                    </select>
                </div>

                <div>
                    <label for="inventarioSeleccionado">Inventario:</label>
                    <select id="inventarioSeleccionado" name="inventario_id" disabled required>
                        <option value="">Primero seleccione un grupo</option>
                    </select>
                </div>

                <div class="report-format-selector">
                    <label>Formato:</label>
                    <div class="format-options">
                        <label class="format-option"><input type="radio" name="formato" value="pdf" checked> <i class="fas fa-file-pdf"></i> PDF</label>
                        <label class="format-option"><input type="radio" name="formato" value="excel"> <i class="fas fa-file-excel"></i> Excel</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Generar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCrearReporteGrupo" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalCrearReporteGrupo')">&times;</span>
            <h2>Reporte de grupo</h2>

            <form id="formReporteDeUnGrupo" action="{{ url('/api/reports/create') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="folderIdGrupo" name="folder_id" />
                <input type="hidden" name="tipoReporte" value="grupo" />

                <div>
                    <label for="nombreReporteOfGrupo">Nombre del reporte:</label>
                    <input type="text" id="nombreReporteOfGrupo" name="nombreReporte" required />
                </div>

                <div>
                    <label for="grupoSeleccionadoOfGrupo">Grupo:</label>
                    <select id="grupoSeleccionadoOfGrupo" name="grupo_id" required>
                        <option value="">Seleccione un grupo</option>
                    </select>
                </div>

                <div class="report-format-selector">
                    <label>Formato:</label>
                    <div class="format-options">
                        <label class="format-option"><input type="radio" name="formato" value="pdf" checked> <i class="fas fa-file-pdf"></i> PDF</label>
                        <label class="format-option"><input type="radio" name="formato" value="excel"> <i class="fas fa-file-excel"></i> Excel</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Generar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCrearReporteTodos" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalCrearReporteTodos')">&times;</span>
            <h2>Reporte de todos los inventarios</h2>

            <form id="formReporteDeTodosLosInventarios" action="{{ url('/api/reports/create') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="folderIdTodosLosInventarios" name="folder_id" />
                <input type="hidden" name="tipoReporte" value="allInventories" />

                <div>
                    <label for="nombreReporteDeTodosLosInventarios">Nombre del reporte:</label>
                    <input type="text" id="nombreReporteDeTodosLosInventarios" name="nombreReporte" required />
                </div>

                <div class="report-format-selector">
                    <label>Formato:</label>
                    <div class="format-options">
                        <label class="format-option"><input type="radio" name="formato" value="pdf" checked> <i class="fas fa-file-pdf"></i> PDF</label>
                        <label class="format-option"><input type="radio" name="formato" value="excel"> <i class="fas fa-file-excel"></i> Excel</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Generar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCrearReporteBienes" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalCrearReporteBienes')">&times;</span>
            <h2>Reporte de bienes</h2>

            <form id="formReporteDeBienes" action="{{ url('/api/reports/create') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="folderIdDeBienes" name="folder_id" />
                <input type="hidden" name="tipoReporte" value="goods" />

                <div>
                    <label for="nombreReporteDeBienes">Nombre del reporte:</label>
                    <input type="text" id="nombreReporteDeBienes" name="nombreReporte" required />
                </div>

                <div class="report-format-selector">
                    <label>Formato:</label>
                    <div class="format-options">
                        <label class="format-option"><input type="radio" name="formato" value="pdf" checked> <i class="fas fa-file-pdf"></i> PDF</label>
                        <label class="format-option"><input type="radio" name="formato" value="excel"> <i class="fas fa-file-excel"></i> Excel</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Generar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCrearReporteEquipos" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalCrearReporteEquipos')">&times;</span>
            <h2>Reporte de equipos</h2>

            <form id="formReporteDeEquipos" action="{{ url('/api/reports/create') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="folderIdDeEquipos" name="folder_id" />
                <input type="hidden" name="tipoReporte" value="serial" />

                <div>
                    <label for="nombreReporteDeEquipos">Nombre del reporte:</label>
                    <input type="text" id="nombreReporteDeEquipos" name="nombreReporte" required />
                </div>

                <div class="report-format-selector">
                    <label>Formato:</label>
                    <div class="format-options">
                        <label class="format-option"><input type="radio" name="formato" value="pdf" checked> <i class="fas fa-file-pdf"></i> PDF</label>
                        <label class="format-option"><input type="radio" name="formato" value="excel"> <i class="fas fa-file-excel"></i> Excel</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Generar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCrearReporteDadosDeBaja" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalCrearReporteDadosDeBaja')">&times;</span>
            <h2>Reporte de dados de baja</h2>

            <form id="formReporteDeDadosDeBaja" action="{{ url('/api/reports/create') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="folderIdDadosDeBaja" name="folder_id" />
                <input type="hidden" name="tipoReporte" value="removedGoods" />

                <div>
                    <label for="nombreReporteDeDadosDeBaja">Nombre del reporte:</label>
                    <input type="text" id="nombreReporteDeDadosDeBaja" name="nombreReporte" required />
                </div>

                <div class="report-format-selector">
                    <label>Formato:</label>
                    <div class="format-options">
                        <label class="format-option"><input type="radio" name="formato" value="pdf" checked> <i class="fas fa-file-pdf"></i> PDF</label>
                        <label class="format-option"><input type="radio" name="formato" value="excel"> <i class="fas fa-file-excel"></i> Excel</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Generar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCrearReporteHistorial" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalCrearReporteHistorial')">&times;</span>
            <h2>Reporte de historial</h2>

            <form id="formReporteDeHistorial" action="{{ url('/api/reports/create') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="folderIdHistorial" name="folder_id" />
                <input type="hidden" name="tipoReporte" value="historial" />

                <div>
                    <label for="nombreReporteDeHistorial">Nombre del reporte:</label>
                    <input type="text" id="nombreReporteDeHistorial" name="nombreReporte" required />
                </div>

                <div class="report-format-selector">
                    <label>Formato:</label>
                    <div class="format-options">
                        <label class="format-option"><input type="radio" name="formato" value="pdf" checked> <i class="fas fa-file-pdf"></i> PDF</label>
                        <label class="format-option"><input type="radio" name="formato" value="excel"> <i class="fas fa-file-excel"></i> Excel</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Generar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalRenombrarReporte" class="modal">
        <div class="modal-content modal-content-medium">
            <span class="close" onclick="ocultarModal('#modalRenombrarReporte')">&times;</span>
            <h2>Renombrar reporte</h2>

            <form id="formRenombrarReporte" action="{{ url('/api/reports/rename') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" id="reporteRenombrarId" name="report_id" />

                <div>
                    <label for="reporteRenombrarNombre">Nuevo nombre:</label>
                    <input type="text" id="reporteRenombrarNombre" name="nombre" required />
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn submit-btn">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initReportsModule === 'function') {
                    initReportsModule();
                }
            });
        </script>
    @endonce
</div>
@endsection
