@extends('layouts.app')

@section('title', 'Bienes')

@section('content')
<div class="content">

    <div class="goods-header">
        <h2>Lista de bienes</h2>

        {{-- Botón subir Excel --}}
        <label class="excel-upload-btn" title="Subir Excel" onclick="toggleExcelUploadUI()">
            <i class="fas fa-file-excel"></i>
        </label>
    </div>

    <div class="top-bar">
        <div class="search-container">
            <input
                type="text"
                id="searchGood"
                placeholder="Buscar o agregar bien"
                class="search-bar searchInput"
            />
            <i class="search-icon fas fa-search"></i>
        </div>

        @if(Auth::user()->role === 'administrador')
            <button id="btnCrear" class="create-btn" onclick="mostrarModal('#modalCrearBien')">
                Crear
            </button>
        @endif

    </div>

    {{-- Cuando NO hay bienes --}}
    @if($dataGoods->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No hay bienes disponibles</p>
        </div>

    {{-- Cuando SÍ hay bienes --}}
    @else
        <div id="bienes-grid" class="bienes-grid">

            @foreach ($dataGoods as $bien)
                <div class="bien-card card-item">

                    {{-- Imagen del bien --}}
                    <img
                        src="{{ $bien->image ? asset('storage/' . $bien->image) : asset('assets/defaults/goods/default.jpg') }}"
                        class="bien-image"
                    />

                    <div class="bien-info">
                        <h3 class="name-item">{{ $bien->name }}
                            <img
                                src="{{ asset('assets/icons/' . ($bien->type == 'Cantidad' ? 'bienCantidad.svg' : 'bienSerial.svg')) }}"
                                alt="Icono tipo bien"
                                class="bien-icon"
                            />
                        </h3>
                        <p>Cantidad: {{ $bien->total_quantity }}</p>
                    </div>

                    @if(Auth::user()->role === 'administrador')
                        <div class="actions">
                            <a class="btn-editar"
                                onclick="ActualizarBien({{ $bien->id }}, '{{ $bien->name }}')">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a class="btn-eliminar"
                                onclick="eliminarBien({{ $bien->id }})">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    @endif

                </div>
            @endforeach

        </div>
    @endif


    {{-- ------------------------- --}}
    {{--  SECCIÓN PARA SUBIR EXCEL --}}
    {{-- ------------------------- --}}
    <div id="excel-upload-content" class="hidden">

        <h3>Cargar datos de bienes desde Excel</h3>

        <div style="margin-bottom: 20px;">
            <a href="{{ route('goods.download-template') }}" 
               class="btn" 
               style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-bottom: 10px;">
                <i class="fas fa-download"></i> Descargar plantilla Excel
            </a>
        </div>

        <div id="excel-upload-area"
            class="excel-upload-area"
            style="border: 2px dashed #ccc; padding: 20px; text-align: center;"
        >
            <p>Arrastra y suelta un archivo aquí o haz clic para seleccionar un archivo</p>

            <input
                type="file"
                id="excelFileInput"
                accept=".xlsx, .xls, .csv"
                class="hidden"
                onchange="handleFileUpload(event)"
            />

            <button id="btn-select-excel" class="select-btn"
                onclick="document.getElementById('excelFileInput').click()">
                Seleccionar archivo
            </button>
        </div>

        <br>

        <h3>Previsualización de datos</h3>
        <div id="excel-preview-table">
            <table class="hidden">
                <thead>
                    <tr>
                        <th>Bien</th>
                        <th>Tipo</th>
                        <th>Imagen</th>
                    </tr>
                </thead>
                <tbody id="excel-preview-body">
                </tbody>
            </table>
        </div>

        <button onclick="btnClearExcelUploadUI()" class="btn">Cancelar</button>
        <button id="btnEnviarExcel" class="btn create-btn"
            onclick="sendGoodsData(collectGoodsData())"
            disabled>
            Enviar
        </button>

    </div>

    {{-- MODALES --}}
    <x-modal.good mode="create" />
    <x-modal.good mode="edit" />
    
    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                iniciarBusqueda('searchGood');
            });
        </script>
    @endonce

</div>
@endsection
