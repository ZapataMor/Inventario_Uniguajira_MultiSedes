{{-- inventories/goods-inventory.blade.php --}}
@extends('layouts.app')

@section('title', 'Bienes del Inventario')

@section('content')
<div id="goods-inventory" class="content">

    <div class="back-and-title">
        <div>
            <span class="location">{{ $inventory->name }}</span>
            <span class="sub-info">Responsable: {{ $inventory->responsible ?? 'N/A' }}</span>
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
@endsection
