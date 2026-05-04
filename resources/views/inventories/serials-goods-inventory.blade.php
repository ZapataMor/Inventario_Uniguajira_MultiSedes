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
                    data-inventory-id="{{ $inventory->id }}"
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
                <button class="control-btn" type="button" title="Ver mantenimientos"
                        onclick="event.stopPropagation(); if (typeof btnVerMantenimientos === 'function') btnVerMantenimientos();">
                    <i class="fas fa-wrench"></i>
                </button>
                <button class="control-btn" type="button" title="Cambiar inventario" onclick="event.stopPropagation(); if (typeof btnCambiarInventarioSerial === 'function') { btnCambiarInventarioSerial(); } else if (typeof window.openSerialMoveFallback === 'function') { window.openSerialMoveFallback(); }">
                    <i class="fas fa-exchange-alt"></i>
                </button>
                <button class="control-btn" type="button" title="Dar de baja" onclick="event.stopPropagation(); if (typeof btnDarDeBajaBienSerial === 'function') btnDarDeBajaBienSerial();">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z" />
                        <path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087Zm6.163 3.75A.75.75 0 0 1 10 12h4a.75.75 0 0 1 0 1.5h-4a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button class="control-btn" type="button" title="Editar" onclick="event.stopPropagation(); if (typeof btnEditarBienSerial === 'function') btnEditarBienSerial();">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                        <path d="M5.25 5.25a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3V13.5a.75.75 0 0 0-1.5 0v5.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V8.25a1.5 1.5 0 0 1 1.5-1.5h5.25a.75.75 0 0 0 0-1.5H5.25Z" />
                    </svg>
                </button>
                <button class="control-btn" type="button" title="Eliminar" onclick="event.stopPropagation(); if (typeof btnEliminarBienSerial === 'function') btnEliminarBienSerial();">
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
                        src="{{ !empty($serial->image) ? route('assets.image', ['path' => $serial->image]) : asset('assets/defaults/goods/default.jpg') }}"
                        class="bien-image"
                        onerror="this.src='{{ asset('assets/defaults/goods/default.jpg') }}'"
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
    <x-modal.inventory.good-inventory-move-serial />
    <x-modal.inventory.good-inventory-maintenances />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initGoodsSerialsInventoryFunctions();
            });
        </script>
    @endonce

</div>
@endsection
