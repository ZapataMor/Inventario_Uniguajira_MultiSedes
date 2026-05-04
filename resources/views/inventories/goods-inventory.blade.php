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
                <i class="fas fa-file-excel "></i>
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
    {{-- Barra de acción para multi-selección --}}
    <div id="batch-action-bar" class="control-bar">
        <span class="selected-name batch-count">0 seleccionados</span>
        <div class="control-actions">
            <button class="control-btn" type="button" title="Mover seleccionados"
                    onclick="btnMoverSeleccionados()">
                <i class="fas fa-exchange-alt"></i>
                <span>Mover</span>
            </button>
            <button class="control-btn" type="button" title="Limpiar selección"
                    onclick="clearMultiSelection()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    {{-- Barra de control para bienes --}}
    <div id="control-bar-good" class="control-bar">
        <div class="selected-name">1 seleccionado</div>

        <div class="control-actions">

            {{-- cambio de inventario --}}
            <button class="control-btn"
                    type="button"
                    title="Cambiar inventario"
                    onclick="
                        event.stopPropagation();
                        if (typeof btnCambiarInventario === 'function') {
                            btnCambiarInventario();
                        } else {
                            if (!selectedItem || selectedItem.type !== 'good') {
                                showToast({ success: false, message: 'No se ha seleccionado un bien.' });
                                return;
                            }

                            const card = selectedItem.element;
                            const idInventario = document.getElementById('inventory-name').getAttribute('data-id');
                            const assetType = card.dataset.assetType;

                            document.getElementById('moverBienId').value = selectedItem.id;
                            document.getElementById('moverInventarioOrigenId').value = idInventario;
                            document.getElementById('moverNombreBien').value = selectedItem.name;

                            const cantidadSection = document.getElementById('moverCantidadSection');
                            const cantidadInput = document.getElementById('moverCantidad');

                            if (assetType === 'Cantidad') {
                                cantidadSection.style.display = '';
                                document.getElementById('moverCantidadDisponible').value = card.dataset.cantidad;
                                cantidadInput.setAttribute('max', card.dataset.cantidad);
                                cantidadInput.value = 1;
                                cantidadInput.required = true;
                            } else {
                                cantidadSection.style.display = 'none';
                                cantidadInput.required = false;
                                cantidadInput.value = '';
                            }

                            const selectGrupo = document.getElementById('moverGrupoDestino');
                            const selectInventario = document.getElementById('moverInventarioDestino');

                            selectGrupo.value = '';
                            selectGrupo.innerHTML = '<option value=\'\'>Seleccionar grupo...</option>';
                            selectInventario.innerHTML = '<option value=\'\'>Seleccionar inventario...</option>';
                            selectInventario.disabled = true;
                            selectGrupo.onchange = function () {
                                const grupoId = this.value;
                                selectInventario.innerHTML = '<option value=\'\'>Seleccionar inventario...</option>';
                                selectInventario.disabled = true;

                                if (!grupoId) return;

                                fetch('/api/inventories/getByGroupId/' + grupoId, {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                })
                                    .then(r => r.json())
                                    .then(data => {
                                        const inventarios = Array.isArray(data) ? data : (data.inventories ?? []);
                                        inventarios.forEach(inv => {
                                            if (String(inv.id) === String(idInventario)) return;
                                            const opt = document.createElement('option');
                                            opt.value = inv.id;
                                            opt.textContent = inv.nombre;
                                            selectInventario.appendChild(opt);
                                        });
                                        selectInventario.disabled = false;
                                    })
                                    .catch(() => showToast({ success: false, message: 'Error al cargar inventarios.' }));
                            };

                            fetch('/api/groups/getAll', {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                                .then(r => r.json())
                                .then(data => {
                                    const grupos = Array.isArray(data) ? data : (data.groups ?? []);
                                    grupos.forEach(g => {
                                        const opt = document.createElement('option');
                                        opt.value = g.id;
                                        opt.textContent = g.nombre;
                                        selectGrupo.appendChild(opt);
                                    });
                                })
                                .catch(() => showToast({ success: false, message: 'Error al cargar grupos.' }));

                            mostrarModal('#modalCambiarInventarioBien');
                        }
                    ">
                <i class="fas fa-exchange-alt"></i>
            </button>

            {{-- Boton para ver mantenimientos --}}
             <button class="control-btn"
                    type="button"
                    title="Ver mantenimientos"
                    onclick="event.stopPropagation(); if (typeof btnVerMantenimientos === 'function') btnVerMantenimientos();">
                <i class="fas fa-wrench"></i>
            </button>

            {{-- Dar de baja --}}
            <button class="control-btn"
                    type="button"
                    title="Dar de baja"
                    onclick="event.stopPropagation(); if (typeof btnDarDeBajaBienCantidad === 'function') btnDarDeBajaBienCantidad();">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z" />
                        <path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087Zm6.133 2.845a.75.75 0 0 1 1.06 0l1.72 1.72 1.72-1.72a.75.75 0 1 1 1.06 1.06l-1.72 1.72 1.72 1.72a.75.75 0 1 1-1.06 1.06L12 15.685l-1.72 1.72a.75.75 0 1 1-1.06-1.06l1.72-1.72-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>               
            </button>

            {{-- Cambiar cantidad --}}
            <button class="control-btn"
                    type="button"
                    title="Cambiar cantidad"
                    onclick="event.stopPropagation(); if (typeof btnEditarBienCantidad === 'function') btnEditarBienCantidad();">
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
                    type="button"
                    title="Eliminar"
                    onclick="event.stopPropagation(); if (typeof btnEliminarBienCantidad === 'function') btnEliminarBienCantidad();">
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
                    @if(Auth::user()->isAdministrator())
                    <label class="multi-check-label"
                           onclick="event.stopPropagation()">
                        <input type="checkbox"
                               class="multi-check-input"
                               onclick="event.stopPropagation()"
                               onchange="toggleMultiSelectItem(this.closest('.bien-card'), event)">
                    </label>
                    @endif

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
    <x-modal.inventory.good-inventory-batch-move />
    <x-modal.inventory.good-inventory-maintenances />
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
