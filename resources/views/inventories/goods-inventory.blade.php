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
    />

    @if(Auth::user()->role === 'administrador')
    {{-- Barra de control para bienes --}}
    <div id="control-bar-good" class="control-bar">
        <div class="selected-name">1 seleccionado</div>

        <div class="control-actions">

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
                    @if (Auth::user()->role === 'administrador')
                        data-id="{{ $asset->asset_id }}"
                        data-name="{{ $asset->asset }}"
                        data-cantidad="{{ $asset->quantity }}"
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
                        src="{{ asset('storage/' . $asset->image ?? 'assets/uploads/img/goods/default.jpg') }}"
                        class="bien-image"
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

    {{-- MODALES: responsable (separate component) --}}
    <x-modal.inventory.good-inventory-create />
    <x-modal.inventory.good-inventory-edit-quantity />
    <x-modal.inventory.good-inventory-remove />
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
