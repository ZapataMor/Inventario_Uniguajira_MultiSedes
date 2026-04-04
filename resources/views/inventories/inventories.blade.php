{{-- resources/views/inventories/inventories.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventarios del grupo')

@section('content')
<div class="content">

    <div class="inventory-header">
        <h1>Inventario</h1>
    </div>

    {{-- Título + botón volver --}}
    <div class="back-and-title">
        <span id="group-name" class="location" data-id="{{ $group->id }}">{{ $group->name }}</span>

        <button class="btn-back" onclick="loadContent( '{{ route('inventory.groups') }}', { onSuccess: () => initGroupFunctions() } )">
            <i class="fas fa-arrow-left me-2"></i>
            <span>Volver</span>
        </button>
    </div>

    <x-generals.top-bar
        id="searchInventory"
        placeholder="Buscar inventario..."
        modal="#modalCrearInventario"
    />

    {{-- Barra de control --}}
    @if(Auth::user()->isAdministrator())
        <div id="control-bar-inventory" class="control-bar">
            <div class="selected-name">1 seleccionado</div>
            <div class="control-actions">
                <button class="control-btn" title="Renombrar" onclick="btnRenombrarInventario()">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="control-btn" title="Eliminar" onclick="btnEliminarInventario()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- GRID DE INVENTARIOS --}}
    <div class="card-grid">
@php
    // dd($inventories)
@endphp
        @if($inventories->isEmpty())
            <div class="empty-state">
                <i class="fas fa-folder-open fa-3x"></i>
                <p>No hay inventarios disponibles</p>
            </div>
        @else

            @foreach ($inventories as $inventory)
                <div class="card card-item"
                    @if(Auth::user()->isAdministrator())
                        data-id="{{ $inventory->id }}"
                        data-name="{{ $inventory->name }}"
                        data-type="inventory"
                        onclick="toggleSelectItem(this)"
                    @endif
                >

                    {{-- Icono --}}
                    <div class="card-left">
                        <i class="fas fa-folder icon-folder"></i>
                    </div>

                    {{-- Info --}}
                    <div class="card-center">
                        <div class="title name-item">
                            {{ $inventory->name }}
                        </div>
                        <div class="stats">
                            <span class="stat-item">
                                <i class="fas fa-shapes"></i>
                                {{ $inventory->total_asset_types ?? 0 }} tipos
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-boxes"></i>
                                {{ $inventory->total_assets ?? 0 }} bienes
                            </span>
                        </div>
                    </div>

                    {{-- Botón Abrir AJAX --}}
                    <div class="card-right">
                        <button class="btn-open"
                                onclick="loadContent( '{{ route('inventory.goods', ['groupId' => $group->id, 'inventoryId' => $inventory->id]) }}',
                                                        { onSuccess: () => initGoodsInventoryFunctions() } )"
                        >
                            <i class="fas fa-external-link-alt"></i> Abrir
                        </button>
                    </div>

                </div>
            @endforeach

        @endif
    </div>

    {{-- MODALES --}}
    <x-modal.inventory mode="create" group_id="{{ $group->id }}" />
    <x-modal.inventory mode="rename" />


    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initInventoryFunctions();
            });
        </script>
    @endonce

</div>
@endsection
