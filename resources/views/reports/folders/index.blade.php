@extends('layouts.app')

@section('title', 'Carpetas de Reportes')

@section('content')
<div class="container content">

    <h1>Carpetas</h1>

    {{-- TOP BAR --}}
    <x-generals.top-bar
        id="searchFolder"
        placeholder="Buscar Carpeta"
        :modal="Auth::user()->role === 'administrador' ? '#modalCrearCarpeta' : null"
    />

    {{-- BARRA DE CONTROL (ADMIN) --}}
    @if(Auth::user()->role === 'administrador')
        <div id="control-bar-folder" class="control-bar hidden">
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

    {{-- GRID DE CARPETAS --}}
    <div class="report-grid">

        @if($folders->isEmpty())

            <div class="report-empty-state">
                <i class="fas fa-folder fa-3x"></i>
                <p>No hay carpetas de reportes disponibles</p>
            </div>

        @else
            @foreach($folders as $folder)
                <div
                    class="report-folder-card card-item"
                    @if(Auth::user()->role === 'administrador')
                        data-id="{{ $folder->id }}"
                        data-name="{{ $folder->name }}"
                        data-type="folder"
                        onclick="toggleSelectItem(this)"
                    @endif
                >
                    {{-- ICONO --}}
                    <div class="report-folder-left">
                        <i class="fas fa-folder fa-2x report-folder-icon"></i>
                    </div>

                    {{-- NOMBRE --}}
                    <div class="report-folder-center">
                        <div class="report-folder-name name-item">
                            {{ $folder->name }}
                        </div>

                        <div class="report-folder-stats">
                            <span class="report-stat-item">
                                <i class="fas fa-file-alt"></i>
                                {{ $folder->reports_count }} reportes
                            </span>
                        </div>
                    </div>

                    {{-- Botón abrir (AJAX) --}}
                    <div class="report-folder-right">
                        <button
                            class="btn-open"
                            onclick="loadContent(
                                '{{ route('reports.folder', $folder->id) }}',
                                { onSuccess: () => initReportFunctions() }
                            )"
                        >
                            <i class="fas fa-external-link-alt"></i> Abrir
                        </button>
                    </div>
                </div>
            @endforeach
        @endif

    </div>

    {{-- CONTENIDO DE REPORTES (AJAX) --}}
    <div id="report-content" class="hidden">

        <div class="report-back-and-title">
            <span id="folder-name" class="location">Reportes</span>
            <button class="report-btn-back" onclick="cerrarCarpeta()">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
        </div>

        <x-generals.top-bar
            id="searchReport"
            placeholder="Buscar Reporte"
        />

        {{-- OPCIONES DE GENERACIÓN --}}
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
                </ul>
            </div>
        </div>

        {{-- BARRA CONTROL REPORTES --}}
        @if(Auth::user()->role === 'administrador')
            <div id="control-bar-report" class="control-bar hidden">
                <div class="selected-name">0 seleccionados</div>
                <div class="control-actions">
                    <button class="control-btn" onclick="btnRenombrarReporte()">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="control-btn" onclick="btnEliminarReporte()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endif

        <div id="report-content-item" class="report-item-grid"></div>

    </div>

    {{-- MODALES --}}
    <x-modal.report.create-folder />
    <x-modal.report.rename-folder />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initFolderFunctions();
            });
        </script>
    @endonce

</div>
@endsection
