@extends('layouts.app')

@section('title', 'Catalogo de bienes')

@section('content')

{{-- Define el contexto de visualización de la vista: catálogo general o portal por sedes --}}
@php
    $isPortalCatalog = $isPortalCatalog ?? false;
    $goodsBySede = $goodsBySede ?? collect();
@endphp

<div class="content">

    <div class="goods-header">
        <h2>Catalogo de bienes</h2>
    </div>

{{-- Componente de barra superior con buscador y acceso al modal de creación --}}
    <x-generals.top-bar
        id="searchGood"
        placeholder="Buscar bien..."
        modal="#modalCrearBien"
        canCreate="{{ $isPortalCatalog ? 'false' : 'true' }}"
    >

{{-- Botón de carga masiva desde Excel, visible solo para administradores fuera del portal --}}
        @if (Auth::user()->isAdministrator() && ! $isPortalCatalog)
            <button class="excel-btn excel-upload-btn" title="Cargar bienes desde Excel"
                onclick="loadContent('{{ route('goods.excel-upload') }}', { onSuccess: () => initGoodsExcelUpload() })">
                <i class="fas fa-file-excel"></i>
            </button>
        @endif
    </x-generals.top-bar>

{{-- Muestra el estado vacío cuando no existen bienes registrados --}}
    @if($dataGoods->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No hay bienes disponibles</p>
        </div>

    {{-- Renderiza el catálogo cuando existen bienes registrados --}}
    @else

        {{-- En modo portal, los bienes se agrupan y se muestran por sede --}}
        @if($isPortalCatalog)
            <div class="goods-sede-list">
                @foreach ($goodsBySede as $sedeData)
                    <details
                        class="goods-sede-dropdown"
                        data-sede-dropdown
                    >

                        {{-- Encabezado desplegable que muestra el nombre de la sede y la cantidad de bienes --}}
                        <summary class="goods-sede-summary">
                            <span class="goods-sede-title">{{ $sedeData['dropdown_label'] }}</span>
                            <span class="goods-sede-count">
                                <span data-visible-count>{{ $sedeData['goods']->count() }}</span> bienes
                            </span>
                        </summary>

                        {{-- Contenedor del contenido asociado a cada sede --}}
                        <div class="goods-sede-body">
                            @if($sedeData['goods']->isEmpty())
                                <p class="goods-sede-empty">No hay bienes disponibles en esta sede.</p>

                            {{-- Cuadrícula de bienes correspondiente a la sede actual --}}
                            @else
                                <div class="bienes-grid">
                                    @foreach ($sedeData['goods'] as $bien)
                                        <div class="bien-card card-item">
                                            <img
                                                src="{{ $bien->image ? route('assets.image', ['path' => $bien->image]) : asset('assets/defaults/goods/default.jpg') }}"
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
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Mensaje auxiliar para filtros sin resultados dentro de una sede --}}
                                <p class="goods-sede-filter-empty hidden" data-sede-empty>
                                    No hay resultados para esta sede con el filtro actual.
                                </p>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>

        {{-- En modo normal, se muestra la cuadrícula de bienes de la sede actual --}}
        @else
            <div id="bienes-grid" class="bienes-grid">
                @foreach ($dataGoods as $bien)
                    <div class="bien-card card-item">

                        {{-- Muestra la imagen del bien o una imagen por defecto si no existe --}}
                        <img
                            src="{{ $bien->image ? route('assets.image', ['path' => $bien->image]) : asset('assets/defaults/goods/default.jpg') }}"
                            class="bien-image"
                        />

                        {{-- Muestra la información principal del bien y el ícono según su tipo --}}
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

                        {{-- Acciones de edición y eliminación visibles solo para administradores --}}
                        @if(Auth::user()->isAdministrator())
                            <div class="actions">

                                <a class="btn-detalles"
                                    onclick="btnDetallesBien({{ $bien->id }})">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a class="btn-editar"
                                    onclick="btnEditarBien({{ $bien->id }}, '{{ $bien->name }}')">
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
    @endif

    {{-- Carga los modales de creación y edición solo para administradores fuera del portal --}}
    @if (Auth::user()->isAdministrator() && ! $isPortalCatalog)
        <x-modal.goods.good mode="create" />
        <x-modal.goods.good mode="edit" />
    @endif

    {{-- Modal para ver ubicaciones del bien --}}
    <x-modal.goods.show-locations />

    {{-- Inicializa la lógica de los formularios cuando el DOM termina de cargarse --}}
    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initFormsBien === 'function') {
                    initFormsBien();
                }
            });
        </script>
    @endonce

</div>
@endsection