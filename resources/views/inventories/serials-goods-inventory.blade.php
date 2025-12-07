@extends('layouts.app')

@section('title', 'Bienes Seriales')

@section('content')
<div id="serials-goods-inventory" class="content">

    <div class="back-and-title">
        <div>
            <span class="location">Bienes Seriales - {{ $inventory->name }}</span>
        </div>

        <button class="btn-back" onclick="loadContent('{{ route('inventory.goods', ['groupId' => $inventory->group_id, 'inventoryId' => $inventory->id]) }}')">
            <i class="fas fa-arrow-left me-2"></i>
            <span>Volver</span>
        </button>
    </div>

    {{-- buscador --}}



    {{-- barra de control --}}

            <div id="control-bar-serial-good" class="control-bar">
                <div class="selected-name">1 seleccionado</div>
                <div class="control-actions">
                    <button class="control-btn" title="Editar" onclick="btnEditarBienSerial()">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="control-btn" title="Eliminar" onclick="btnEliminarBienSerial()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

    @if($serials->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No hay bienes seriales disponibles.</p>
        </div>
    @else
        <div class="bienes-grid">
            @foreach($serials as $serial)
                <div class="bien-card card-item"
                    data-id="{{ $serial->asset_equipment_id }}"
                    data-inventory-id="{{ $serial->inventory_id }}"
                    data-bien-id="{{ $serial->asset_id }}"
                    data-name="{{ $serial->asset }}"
                    data-description="{{ $serial->description }}"
                    data-brand="{{ $serial->brand }}"
                    data-model="{{ $serial->model }}"
                    data-serial="{{ $serial->serial }}"
                    data-status="{{ $serial->status }}"
                    data-color="{{ $serial->color }}"
                    data-condition="{{ $serial->technical_conditions }}"
                    data-entry-date="{{ $serial->entry_date }}"
                    data-type="serial"
                    onclick="toggleSelectItem(this)"
                >
                    <img
                        src="{{ asset('storage/' . $serial->image ?? 'assets/uploads/img/goods/default.jpg') }}"
                        class="bien-image"
                    />

                    <div class="bien-info">
                        <h3 class="name-item">
                            {{ $serial->asset }}
                            <img
                                src="{{ asset('assets/icons/bienSerial.svg') }}"
                                class="bien-icon"
                            />
                        </h3>

                        <p><b>Serial:</b> {{ $serial->serial }}</p>
                        <p><b>Marca:</b> {{ $serial->brand ?? 'N/A' }}</p>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

    {{-- MODALES --}}
    <x-modal.good-inventory-edit-serial />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                //
            });
        </script>
    @endonce

</div>
@endsection
