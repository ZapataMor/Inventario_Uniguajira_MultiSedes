@extends('layouts.app')

@section('title', 'Grupos de Inventario')

@section('content')
@php
    $isPortalInventoryCatalog = $isPortalInventoryCatalog ?? false;
    $groupsBySede = $groupsBySede ?? collect();
@endphp
<div class="content">

    <div class="inventory-header">
        <h1>Inventario</h1>
    </div>

    <h2 class="location">Grupos</h2>

    <x-generals.top-bar
        id="searchGroup"
        placeholder="Buscar grupo..."
        modal="#modalCrearGrupo"
        canCreate="{{ $isPortalInventoryCatalog ? 'false' : 'true' }}"
    />

    {{-- Barra de control para selección múltiple --}}
    @if(Auth::user()->isAdministrator() && ! $isPortalInventoryCatalog)
    <div id="control-bar-group" class="control-bar">
        <div class="selected-name">1 seleccionado</div>
        <div class="control-actions">
            <button class="control-btn" title="Renombrar" onclick="btnRenombrarGrupo()">
                <i class="fas fa-pen"></i>
            </button>

            <button class="control-btn" title="Eliminar" onclick="btnEliminarGrupo()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
    @endif

    @if($groups->isEmpty())
        <div class="empty-state">
            <i class="fas fa-layer-group fa-3x"></i>
            <p>No hay grupos disponibles</p>
        </div>
    @elseif($isPortalInventoryCatalog)
        <div class="inventory-sede-list">
            @foreach ($groupsBySede as $sedeData)
                <details class="inventory-sede-dropdown" data-sede-dropdown>
                    <summary class="inventory-sede-summary">
                        <span class="inventory-sede-title">{{ $sedeData['dropdown_label'] }}</span>
                        <span class="inventory-sede-count">
                            <span data-visible-count>{{ $sedeData['groups']->count() }}</span> grupos
                        </span>
                    </summary>

                    <div class="inventory-sede-body">
                        @if($sedeData['groups']->isEmpty())
                            <p class="inventory-sede-empty">No hay grupos disponibles en esta sede.</p>
                        @else
                            <div class="card-grid inventory-sede-grid">
                                @foreach ($sedeData['groups'] as $group)
                                    <div class="card card-item">
                                        <div class="card-left">
                                            <i class="fas fa-layer-group icon-folder"></i>
                                        </div>

                                        <div class="card-center">
                                            <div class="title name-item">
                                                {{ $group->name }}
                                            </div>

                                            <div class="stats">
                                                <span class="stat-item">
                                                    <i class="fas fa-folder"></i>
                                                    {{ $group->inventories_count }}
                                                    <span class="hide-on-mobile">inventarios</span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="card-right">
                                            <button
                                                class="btn-open"
                                                onclick="loadContent('{{ route('portal.switch', ['slug' => $sedeData['tenant_slug'], 'redirect' => '/group/' . $group->id, 'inplace' => 1]) }}', { updateHistory: false, onSuccess: () => initInventoryFunctions() })"
                                            >
                                                <i class="fas fa-external-link-alt"></i> Abrir
                                            </button>
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
    @else
        <div class="card-grid">
            @foreach ($groups as $group)
                <div
                    class="card card-item"
                    @if(Auth::user()->isAdministrator())
                        data-id="{{ $group->id }}"
                        data-name="{{ $group->name }}"
                        data-type="group"
                        onclick="toggleSelectItem(this)"
                    @endif
                >
                    {{-- Ícono --}}
                    <div class="card-left">
                        <i class="fas fa-layer-group icon-folder"></i>
                    </div>

                    {{-- Nombre y estadísticas --}}
                    <div class="card-center">
                        <div class="title name-item">
                            {{ $group->name }}
                        </div>

                        <div class="stats">
                            <span class="stat-item">
                                <i class="fas fa-folder"></i>
                                {{ $group->inventories_count }}
                                <span class="hide-on-mobile">inventarios</span>
                            </span>
                        </div>
                    </div>

                    {{-- Botón abrir (AJAX) --}}
                    <div class="card-right">
                        <button
                            class="btn-open"
                            onclick="loadContent('{{ route('inventory.inventories', $group->id) }}', { onSuccess: () => initInventoryFunctions() } )"
                        >
                            <i class="fas fa-external-link-alt"></i> Abrir
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- MODALES --}}
    @if(Auth::user()->isAdministrator() && ! $isPortalInventoryCatalog)
        <x-modal.group mode="create" />
        <x-modal.group mode="rename" />
    @endif

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                initGroupFunctions();
            });
        </script>
    @endonce

</div>
@endsection
