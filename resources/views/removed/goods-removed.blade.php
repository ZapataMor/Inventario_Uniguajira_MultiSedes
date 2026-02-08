@extends('layouts.app')

@section('title', 'Bienes Dados de Baja')

@section('content')
<div id="goods-removed" class="content">

    <div class="inventory-header">
        <h1>Bienes Dados de Baja</h1>
    </div>

    <x-generals.top-bar
        id="searchRemovedGoods"
        placeholder="Buscar por nombre o motivo..."
        :canCreate="false"
    />

    @if($removedAssets->isEmpty())
        <div class="empty-state">
            <i class="fas fa-check-circle fa-3x"></i>
            <p>No hay bienes dados de baja</p>
        </div>
    @else
        <div class="bienes-grid">
            @foreach($removedAssets as $asset)
                <div class="bien-card card-item"
                    data-search="{{ strtolower($asset->asset_name . ' ' . $asset->reason) }}"
                    onclick="btnViewRemovedDetails({{ $asset->id }})"
                    style="cursor: pointer;">

                    {{-- Imagen --}}
                    <img
                        src="{{ asset('storage/' . ($asset->image ?? 'assets/uploads/img/goods/default.jpg')) }}"
                        class="bien-image"
                        onerror="this.src='{{ asset('assets/uploads/img/goods/default.jpg') }}'"
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

                        <p><b>Cantidad:</b> {{ $asset->quantity }}</p>
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
    <div class="modal-content">
        <span class="close" onclick="ocultarModal('#modalRemovedDetails')">&times;</span>
        <h2>Detalles del Bien Dado de Baja</h2>
        <div id="removedDetailsContent" class="form-container">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initRemovedGoodsFunctions();
        });
    </script>
@endonce

</div>
@endsection
