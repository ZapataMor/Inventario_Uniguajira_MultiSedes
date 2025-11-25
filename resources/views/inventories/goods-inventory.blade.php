{{-- inventories/goods-inventory.blade.php --}}
@extends('layouts.app')

@section('title', 'Bienes del Inventario')

@section('content')
<div id="goods-inventory" class="content">

    <div class="inventory-header">
        <h1>Inventario</h1>

        <div class="inventory-controls">
            <div class="status-control">
                <form id="estadoInventarioForm" method="post" action="/api/inventories/updateEstado" class="status-indicator">
                    <input type="hidden" id="estado_id_inventario" name="id_inventario" value="1">
                    <input type="hidden" id="estado_value" name="estado">

                    <span class="status-label">Estado:</span>
                    <div class="status-lights">

                        {{-- MALO = 3 --}}
                        <div class="light light-red {{ $inventory->conservation_status == 'bad' ? 'active' : 'inactive' }}"
                            onclick="
                                this.closest('form').estado.value='3';
                                this.closest('form').querySelector('button[type=submit]').click();
                            "
                            title="Mal estado"></div>

                        {{-- REGULAR = 2 --}}
                        <div class="light light-yellow {{ $inventory->conservation_status == 'regular' ? 'active' : 'inactive' }}"
                            onclick="
                                this.closest('form').estado.value='2';
                                this.closest('form').querySelector('button[type=submit]').click();
                            "
                            title="Estado regular"></div>

                        {{-- BUENO = 1 --}}
                        <div class="light light-green {{ $inventory->conservation_status == 'good' ? 'active' : 'inactive' }}"
                            onclick="
                                this.closest('form').estado.value='1';
                                this.closest('form').querySelector('button[type=submit]').click();
                            "
                            title="Buen estado"></div>

                    </div>

                    <button type="submit" style="display:none"></button>
                </form>
                <button class="edit-btn" onclick="btnEditarResponsable()" title="Editar responsable">
                    <i class="fas fa-user-edit"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="back-and-title">
        <div>
            <span class="location">{{ $inventory->name }}</span>
            @if ($inventory->responsible)
                <span class="sub-info">Responsable: {{ $inventory->responsible }}</span>
            @endif
        </div>

        <button class="btn-back" onclick="abrirGrupo({{ $inventory->group_id }})">
            <i class="fas fa-arrow-left me-2"></i>
            <span>Volver</span>
        </button>
    </div>

    <div class="top-bar">
        <div class="search-container">
            <input id="searchGoodInventory"
                   class="search-bar searchInput"
                   type="text"
                   placeholder="Buscar bienes…"/>
            <i class="search-icon fas fa-search"></i>
        </div>

        @if(auth()->user()->role === 'administrador')
            <button class="create-btn" onclick="abrirModalCrearBien()">
                Crear
            </button>
        @endif
    </div>

    @if($assets->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No hay bienes en este inventario</p>
        </div>
    @else
        <div class="bienes-grid">
            @foreach($assets as $asset)
                <div class="bien-card card-item"
                    data-id="{{ $asset->asset_id }}"
                    data-name="{{ $asset->asset }}"
                    data-type="{{ $asset->type }}"
                >

                    {{-- Imagen --}}
                    <img
                        src="{{ asset('storage/' . $asset->image ?? 'assets/uploads/img/goods/default.jpg') }}"
                        class="bien-image"
                    />

                    {{-- Info --}}
                    <div class="bien-info">
                        <h3 class="name-item">
                            {{ $asset->asset }}

                            <img
                                src="{{ asset('assets/icons/' . ($asset->type === 'quantity' ? 'bienCantidad.svg' : 'bienSerial.svg')) }}"
                                class="bien-icon"
                            />
                        </h3>

                        <p><b>Cantidad:</b> {{ $asset->quantity }}</p>
                    </div>

                    {{-- Detalle seriales --}}
                    @if($asset->type === 'serial')
                        <div class="actions">
                            <button class="btn-detalle"
                                    onclick="abrirSeriales({{ $asset->asset_id }}, {{ $inventory->id }})">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @endif

</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            iniciarBusqueda('searchGoodInventory');
        });
    </script>
@endonce



@endsection
