@extends('layouts.app')

@section('title', 'Bienes Seriales')

@section('content')
<div id="serials-goods-inventory" class="content">

    <div class="inventory-header">
        <h1>Inventario</h1>
    </div>

    <div class="back-and-title">
        <div class="flex flex-col">

            {{-- ✅ MODIFICACIÓN:
                Antes: data-url usaba $serials[0] (revienta si está vacío)
                Ahora: solo lo mostramos si NO está vacío
            --}}
            @if(!$serials->isEmpty())
                <span id="good-serial-inventory-name"
                    data-url="{{ route('inventory.serials', [
                        'groupId' => $inventory->group_id,
                        'inventoryId' => $inventory->id,
                        'assetId' => $serials[0]->asset_id
                    ]) }}"
                    class="location">
                    Detalles - {{ $serials[0]->asset }}
                </span>
            @else
                <span class="location">
                    Detalles - Bien serial
                </span>
            @endif
            {{-- ✅ FIN MODIFICACIÓN --}}

            <span class="location">Inventario - {{ $inventory->name }}</span>
        </div>

        <button class="btn-back" onclick="loadContent(
                '{{ route('inventory.goods', ['groupId' => $inventory->group_id, 'inventoryId' => $inventory->id]) }}',
                { onSuccess: () => initGoodsInventoryFunctions() })">
            <i class="fas fa-arrow-left me-2"></i>
            <span>Volver</span>
        </button>
    </div>

    {{-- barra de control --}}
    {{-- ✅ MODIFICACIÓN:
        Si está vacío, NO mostramos la barra porque no hay nada seleccionable.
    --}}
    @if(!$serials->isEmpty())
        <div id="control-bar-serial-good" class="control-bar">
            <div class="selected-name">1 seleccionado</div>
            <div class="control-actions">
                <button class="control-btn" title="Dar de baja" onclick="btnDarDeBajaBienSerial()">
                    <i class="fas fa-trash"></i>
                </button>
                <button class="control-btn" title="Editar" onclick="btnEditarBienSerial()">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="control-btn" title="Eliminar" onclick="btnEliminarBienSerial()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    @endif
    {{-- ✅ FIN MODIFICACIÓN --}}

    @if($serials->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No hay bienes seriales disponibles.</p>
        </div>

        {{-- ✅ MODIFICACIÓN:
            Si quedó vacío (ej: eliminaste el último),
            redirigimos automáticamente al inventario general (goods-inventory)
        --}}
        @once
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    loadContent(
                        "{{ route('inventory.goods', ['groupId' => $inventory->group_id, 'inventoryId' => $inventory->id]) }}",
                        { onSuccess: () => initGoodsInventoryFunctions() }
                    );
                });
            </script>
        @endonce
        {{-- ✅ FIN MODIFICACIÓN --}}

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
                    data-type="serial-good"
                    onclick="toggleSelectItem(this)"
                >
                    <img
                        src="{{ asset('storage/' . $serial->image ?? 'assets/uploads/img/goods/default.jpg') }}"
                        class="bien-image"
                    />

                    <div class="bien-info">
                        <h3 class="name-item">
                            <span class="hidden">{{ $serial->serial }}</span>
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
    <x-modal.inventory.good-inventory-edit-serial />
    <x-modal.inventory.good-inventory-remove-serial />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initGoodsSerialsInventoryFunctions();
            });
        </script>
    @endonce

</div>
@endsection
