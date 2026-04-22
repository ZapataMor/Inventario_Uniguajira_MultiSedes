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
                    <input type="hidden" id="estado_id_inventario" name="id_inventario" value="{{ $inventory->id }}">
                    <input type="hidden" id="estado_value" name="estado">

                    <span class="status-label">Estado:</span>
                    <div class="status-lights">

                        {{-- MALO = 3 --}}
                        <div class="light light-red {{ $inventory->conservation_status == 'bad' ? 'active' : 'inactive' }}"
                            @if(Auth::user()->isAdministrator())
                                onclick="
                                    this.closest('form').estado.value='3';
                                    this.closest('form').querySelector('button[type=submit]').click();
                                "
                            @endif
                            title="Mal estado"></div>

                        {{-- REGULAR = 2 --}}
                        <div class="light light-yellow {{ $inventory->conservation_status == 'regular' ? 'active' : 'inactive' }}"
                            @if(Auth::user()->isAdministrator())
                                onclick="
                                    this.closest('form').estado.value='2';
                                    this.closest('form').querySelector('button[type=submit]').click();
                                "
                            @endif
                            title="Estado regular"></div>

                        {{-- BUENO = 1 --}}
                        <div class="light light-green {{ $inventory->conservation_status == 'good' ? 'active' : 'inactive' }}"
                            @if(Auth::user()->isAdministrator())
                                onclick="
                                    this.closest('form').estado.value='1';
                                    this.closest('form').querySelector('button[type=submit]').click();
                                "
                            @endif
                            title="Buen estado"></div>

                    </div>

                    <button type="submit" style="display:none"></button>
                </form>
                @if(Auth::user()->isAdministrator())
                    <button class="edit-btn" onclick="btnEditarResponsable()" title="Editar responsable">
                        <i class="fas fa-user-edit"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="back-and-title">
        <div>
            <span id="inventory-name" class="location" data-id="{{ $inventory->id }}" data-group-id="{{ $inventory->group_id }}" >{{ $inventory->name }}</span>
            @if ($inventory->responsible)
                <span class="sub-info">Responsable: {{ $inventory->responsible }}</span>
            @endif
        </div>

        <button class="btn-back" onclick="loadContent('{{ route('inventory.inventories', $inventory->group_id) }}', { onSuccess: () => initInventoryFunctions() })">
            <i class="fas fa-arrow-left me-2"></i>
            <span>Volver</span>
        </button>
    </div>

    <x-generals.top-bar
        id="searchGoodInventory"
        placeholder="Buscar bien..."
        onclick="btnAbrirModalCrearBien"
    >
        @if(Auth::user()->isAdministrator())
            <button class="excel-btn" onclick="btnAbrirModalExcelInventario()" title="Cargar bienes desde Excel">
                <i class="fas fa-file-excel "></i>  Cargar Excel
            </button>
        @endif
    </x-generals.top-bar>

    {{-- @if(Auth::user()->role === 'administrador')
    <div style="display:flex; justify-content:flex-end; margin-bottom: 0.5rem;">
        <button class="btn create-btn" onclick="btnAbrirModalExcelInventario()" title="Cargar bienes desde Excel"
        style="background-color: #1B5E20; ">
            <i class="fas fa-file-excel"></i> Cargar Excel
        </button>
    </div>
    @endif --}}

    @if(Auth::user()->isAdministrator())
    {{-- Barra de control para bienes --}}
    <div id="control-bar-good" class="control-bar">
        <div class="selected-name">1 seleccionado</div>

        <div class="control-actions">

            {{-- cambio de inventario --}}
            <button class="control-btn"
                    title="Cambiar inventario"
                    onclick="btnCambiarInventario()">
                <i class="fas fa-exchange-alt"></i>
            </button>

            {{-- Dar de baja --}}
            <button class="control-btn"
                    title="Dar de baja"
                    onclick="btnDarDeBajaBienCantidad()">
                <i class="fas fa-trash"></i>
            </button>

            {{-- Cambiar cantidad --}}
            <button class="control-btn"
                    title="Cambiar cantidad"
                    onclick="btnEditarBienCantidad()">
                <i class="fas fa-sort-numeric-up"></i>
            </button>

            {{-- (Pendiente implementación futura)
            <button class="control-btn"
                    title="Mover"
                    onclick="btnMoverBien()">
                <i class="fas fa-exchange-alt"></i>
            </button>
            --}}

            {{-- Eliminar --}}
            <button class="control-btn"
                    title="Eliminar"
                    onclick="btnEliminarBienCantidad()">
                <i class="fas fa-trash"></i>
            </button>

        </div>
    </div>
    @endif


    @if($assets->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No hay bienes en este inventario</p>
        </div>
    @else
        <div class="bienes-grid">
            @foreach($assets as $asset)
                <div class="bien-card card-item"
                    @if (Auth::user()->isAdministrator())
                        data-id="{{ $asset->asset_id }}"
                        data-name="{{ $asset->asset }}"
                        data-cantidad="{{ $asset->quantity }}"
                        data-asset-type="{{ $asset->type }}"
                        data-type="good"
                
                        @if ($asset->type === 'Cantidad')
                            onclick="toggleSelectItem(this)"
                        @else
                            onclick="loadContent('{{ route('inventory.serials', [
                                'groupId' => $inventory->group_id,
                                'inventoryId' => $inventory->id,
                                'assetId' => $asset->asset_id
                            ]) }}', { onSuccess: () => initGoodsSerialsInventoryFunctions() })"
                        @endif
                    @endif
                >
        

                    {{-- Imagen --}}
                    <img
                        src="{{ !empty($asset->image) ? route('assets.image', ['path' => $asset->image]) : asset('assets/defaults/goods/default.jpg') }}"
                        class="bien-image"
                        onerror="this.src='{{ asset('assets/defaults/goods/default.jpg') }}'"
                    />

                    {{-- Info --}}
                    <div class="bien-info">
                        <h3 class="name-item">
                            {{ $asset->asset }}

                            <img
                                src="{{ asset('assets/icons/' . ($asset->type === 'Cantidad' ? 'bienCantidad.svg' : 'bienSerial.svg')) }}"
                                class="bien-icon"
                            />
                        </h3>

                        <p><b>Cantidad:</b> {{ $asset->quantity }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- MODALES --}}
    <x-modal.inventory.good-inventory-create />
    <x-modal.inventory.good-inventory-edit-quantity />
    <x-modal.inventory.good-inventory-remove />
    <x-modal.inventory.good-inventory-move />
    <x-modal.inventory.inventory-responsible />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initGoodsInventoryFunctions();
            });
        </script>
    @endonce

</div>
@endsection
