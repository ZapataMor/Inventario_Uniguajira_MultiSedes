@extends('layouts.app')

@section('title', 'Bienes Dados de Baja')

@section('content')
@php
    $isPortalRemovedCatalog = $isPortalRemovedCatalog ?? false;
    $removedAssetsBySede = $removedAssetsBySede ?? collect();
@endphp
<div id="goods-removed" class="content" data-portal-catalog="{{ $isPortalRemovedCatalog ? '1' : '0' }}">

    <div class="inventory-header">
        <h1>Bienes Dados de Baja</h1>
    </div>

    <div class="mb-5 flex items-center gap-2.5">
        <div class="flex-1">
            <x-generals.top-bar
                id="searchRemovedGoods"
                placeholder="Buscar por nombre o motivo..."
                :canCreate="false"
            />
        </div>

        @if(! $isPortalRemovedCatalog)
        <div class="mb-5">
            <button
                id="btnOpenFilter"
                class="create-btn"
                onclick="openFilterModal()"
                title="Filtrar bienes">
                <i class="fas fa-filter"></i> Filtro
            </button>

            <button
                id="btnClearFilter"
                class="create-btn btn-clear-filter hidden"
                onclick="clearFilters()"
                title="Limpiar filtros">
                <i class="fas fa-times"></i>
            </button>
        </div>
        @endif
    </div>

    @if($isPortalRemovedCatalog)
        <div class="inventory-sede-list">
            @foreach ($removedAssetsBySede as $sedeData)
                <details class="inventory-sede-dropdown" data-sede-dropdown>
                    <summary class="inventory-sede-summary">
                        <span class="inventory-sede-title">{{ $sedeData['dropdown_label'] }}</span>
                        <span class="inventory-sede-count">
                            <span data-visible-count>{{ $sedeData['removed_assets']->count() }}</span> bienes
                        </span>
                    </summary>

                    <div class="inventory-sede-body">
                        @if($sedeData['removed_assets']->isEmpty())
                            <p class="inventory-sede-empty">No hay bienes dados de baja en esta sede.</p>
                        @else
                            <div class="bienes-grid">
                                @foreach($sedeData['removed_assets'] as $asset)
                                    <div class="bien-card card-item cursor-pointer"
                                        data-search="{{ strtolower($asset->asset_name . ' ' . $asset->reason) }}"
                                        onclick="btnViewRemovedDetails({{ $asset->id }}, '{{ $asset->source }}', '{{ $sedeData['tenant_slug'] }}')">

                                        <img
                                            src="{{ !empty($asset->image) ? route('assets.image', ['path' => $asset->image]) : asset('assets/defaults/goods/default.jpg') }}"
                                            class="bien-image"
                                            onerror="this.src='{{ asset('assets/defaults/goods/default.jpg') }}'"
                                        />

                                        <div class="bien-info">
                                            <h3 class="name-item">
                                                {{ $asset->asset_name }}
                                                <img
                                                    src="{{ asset('assets/icons/' . ($asset->type === 'Cantidad' ? 'bienCantidad.svg' : 'bienSerial.svg')) }}"
                                                    class="bien-icon"
                                                />
                                            </h3>

                                            @if($asset->type === 'Cantidad')
                                                <p><b>Cantidad:</b> {{ $asset->quantity }}</p>
                                            @else
                                                <p><b>Serial:</b> {{ $asset->serial ?? 'N/A' }}</p>
                                            @endif

                                            <p><b>Inventario:</b> {{ $asset->inventory_name }}</p>
                                            <p><b>Grupo:</b> {{ $asset->group_name }}</p>
                                            <p><b>Motivo:</b> {{ Str::limit($asset->reason, 50) }}</p>
                                            <p><b>Usuario:</b> {{ $asset->removed_by_user ?? 'N/A' }}</p>
                                            <p><b>Fecha:</b> {{ \Carbon\Carbon::parse($asset->removed_at)->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="inventory-sede-filter-empty hidden" data-sede-empty>
                                No hay resultados para esta sede con el filtro actual.
                            </p>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @elseif($removedAssets->isEmpty())
        <div class="empty-state">
            <i class="fas fa-check-circle fa-3x"></i>
            <p>No hay bienes dados de baja</p>
        </div>
    @else
        <div class="bienes-grid">
            @foreach($removedAssets as $asset)
                <div class="bien-card card-item cursor-pointer"
                    data-search="{{ strtolower($asset->asset_name . ' ' . $asset->reason) }}"
                    onclick="btnViewRemovedDetails({{ $asset->id }}, '{{ $asset->source }}')">

                    {{-- Imagen --}}
                    <img
                        src="{{ !empty($asset->image) ? route('assets.image', ['path' => $asset->image]) : asset('assets/defaults/goods/default.jpg') }}"
                        class="bien-image"
                        onerror="this.src='{{ asset('assets/defaults/goods/default.jpg') }}'"
                    />

                    {{-- Info --}}
                    <div class="bien-info">
                        <h3 class="name-item">
                            {{ $asset->asset_name }}
                            <img
                                src="{{ asset('assets/icons/' . ($asset->type === 'Cantidad' ? 'bienCantidad.svg' : 'bienSerial.svg')) }}"
                                class="bien-icon"
                            />
                        </h3>

                        @if($asset->type === 'Cantidad')
                            <p><b>Cantidad:</b> {{ $asset->quantity }}</p>
                        @else
                            <p><b>Serial:</b> {{ $asset->serial ?? 'N/A' }}</p>
                        @endif

                        <p><b>Inventario:</b> {{ $asset->inventory_name }}</p>
                        <p><b>Grupo:</b> {{ $asset->group_name }}</p>
                        <p><b>Motivo:</b> {{ Str::limit($asset->reason, 50) }}</p>
                        <p><b>Usuario:</b> {{ $asset->removed_by_user ?? 'N/A' }}</p>
                        <p><b>Fecha:</b> {{ \Carbon\Carbon::parse($asset->removed_at)->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Modal de Detalles --}}
<div id="modalRemovedDetails" class="modal">
    <div id="removedFlyoutPanel" class="modal-content flyout-panel">
        <span class="close" onclick="ocultarModal('#modalRemovedDetails')">&times;</span>
        <h2>Detalles del Bien Dado de Baja</h2>

        <div id="removedDetailsContent" class="form-container"></div>
    </div>
</div>

{{-- Modal de Filtros --}}
@if(! $isPortalRemovedCatalog)
<div id="modalFilterRemoved" class="modal">
    <div class="modal-content flyout-panel">
        <span class="close" onclick="ocultarModal('#modalFilterRemoved')">&times;</span>
        <h2>Filtrar Bienes Dados de Baja</h2>

        <div class="form-container">
            @include('components.modal.removed.filter-removed')
        </div>
    </div>
</div>
@endif

{{-- Estilos del flyout (solo comportamiento, no botones) --}}
@once
<style>
    /* Overlay modal */
    #modalRemovedDetails,
    #modalFilterRemoved {
        position: fixed !important;
        top: var(--app-navbar-height, 4.8rem) !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: calc(100vh - var(--app-navbar-height, 4.8rem)) !important;
        min-height: calc(100vh - var(--app-navbar-height, 4.8rem)) !important;
        max-height: calc(100vh - var(--app-navbar-height, 4.8rem)) !important;
        background: rgba(0,0,0,0.45);
        z-index: 9999 !important;
        margin: 0 !important;
        padding: 0 !important;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    #modalRemovedDetails[style*="display: block"],
    #modalRemovedDetails.show,
    #modalRemovedDetails.active,
    #modalRemovedDetails.open,
    #modalFilterRemoved[style*="display: block"],
    #modalFilterRemoved.show,
    #modalFilterRemoved.active,
    #modalFilterRemoved.open {
        opacity: 1 !important;
        visibility: visible !important;
    }

    /* Panel flyout */
    #modalRemovedDetails .flyout-panel,
    #modalFilterRemoved .flyout-panel {
        position: fixed !important;
        top: var(--app-navbar-height, 4.8rem) !important;
        bottom: 0 !important;
        right: 0 !important;
        height: calc(100vh - var(--app-navbar-height, 4.8rem)) !important;
        min-height: calc(100vh - var(--app-navbar-height, 4.8rem)) !important;
        max-height: calc(100vh - var(--app-navbar-height, 4.8rem)) !important;
        width: 42% !important;
        max-width: 700px !important;
        min-width: 420px !important;
        background: white !important;
        border-radius: 0 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        margin: 0 !important;
        padding: 30px 25px !important;
        box-sizing: border-box !important;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        /* will-change: transform; */
        box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    }

    #modalRemovedDetails[style*="display: block"] .flyout-panel,
    #modalRemovedDetails.show .flyout-panel,
    #modalRemovedDetails.active .flyout-panel,
    #modalRemovedDetails.open .flyout-panel,
    #modalFilterRemoved[style*="display: block"] .flyout-panel,
    #modalFilterRemoved.show .flyout-panel,
    #modalFilterRemoved.active .flyout-panel,
    #modalFilterRemoved.open .flyout-panel {
        transform: translateX(0) !important;
    }

    .flyout-panel .close {
        position: absolute;
        top: 20px;
        right: 25px;
        font-size: 28px;
        font-weight: bold;
        color: #aaa;
        cursor: pointer;
        line-height: 1;
        z-index: 10;
    }

    .flyout-panel .close:hover {
        color: #000;
    }

    .flyout-panel h2 {
        margin-top: 0;
        margin-bottom: 20px;
        padding-right: 40px;
    }

    @media (max-width: 768px) {
        .flyout-panel {
            width: 90% !important;
            min-width: 300px !important;
        }
    }
</style>
@endonce

@once
<script>
    document.addEventListener('DOMContentLoaded', () => {
        initRemovedGoodsFunctions();
    });
</script>
@endonce

</div>
@endsection
