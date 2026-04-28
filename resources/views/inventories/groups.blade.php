@extends('layouts.app')

@section('title', 'Grupos de Inventario')

@section('content')
@php
    $isPortalInventoryCatalog = $isPortalInventoryCatalog ?? false;
    $groupsBySede = $groupsBySede ?? collect();
    $groupSearchType = $groupSearchType ?? 'groups';
    $groupSearchTerm = $groupSearchTerm ?? '';
    $groupSearchResults = $groupSearchResults ?? collect();
    $isRemoteGroupSearch = $groupSearchTerm !== '' && $groupSearchType !== 'groups';
@endphp
<div
    class="content"
    data-group-search-root
    data-portal-catalog="{{ $isPortalInventoryCatalog ? '1' : '0' }}"
>

    <div class="inventory-header">
        <h1>Inventario</h1>
    </div>

    <h2 class="location">Grupos</h2>

    <form
        id="groupSearchForm"
        method="GET"
        action="{{ route('inventory.groups') }}"
        onsubmit="return window.submitGroupSearchAjax ? window.submitGroupSearchAjax(this, true) : true"
    >
        @if(request()->boolean('portal'))
            <input type="hidden" name="portal" value="1">
        @endif
    </form>

    <x-generals.top-bar
        id="searchGroup"
        placeholder="{{ $groupSearchType === 'inventories' ? 'Buscar inventario...' : ($groupSearchType === 'goods' ? 'Buscar bien...' : 'Buscar grupo...') }}"
        searchName="search"
        searchValue="{{ $groupSearchTerm }}"
        searchForm="groupSearchForm"
        searchOnInput="clearTimeout(this.form.groupSearchTimer); this.form.groupSearchTimer = setTimeout(() => window.handleGroupSearchInput ? window.handleGroupSearchInput(this.form) : this.form.requestSubmit(), 450)"
        modal="#modalCrearGrupo"
        canCreate="{{ $isPortalInventoryCatalog ? 'false' : 'true' }}"
    >
        <label for="groupSearchMode" class="sr-only">Filtrar por</label>
        <select
            id="groupSearchMode"
            name="search_type"
            form="groupSearchForm"
            onchange="window.handleGroupSearchInput ? window.handleGroupSearchInput(this.form, true) : this.form.requestSubmit()"
            class="h-11 rounded-lg border border-slate-200 bg-white px-3 pr-8 text-sm font-semibold text-slate-700 shadow-sm outline-none transition hover:border-slate-300 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
            aria-label="Filtrar busqueda por"
        >
            <option value="groups" @selected($groupSearchType === 'groups')>Grupos</option>
            <option value="inventories" @selected($groupSearchType === 'inventories')>Inventarios</option>
            <option value="goods" @selected($groupSearchType === 'goods')>Bienes</option>
        </select>

        @if(Auth::user()->isAdministrator() && ! $isPortalInventoryCatalog)
            <button
                type="button"
                class="excel-btn btn-localizacion-excel"
                onclick="abrirExcelLocalizacion()"
                title="Carga masiva por localización">
                <i class="fas fa-file-excel"></i> Carga por localización
            </button>
        @endif
    </x-generals.top-bar>

    <div
        id="groupSearchResults"
        class="mb-5 {{ $isRemoteGroupSearch ? '' : 'hidden' }}"
        aria-live="polite"
        data-group-search-results
    >
        @if($isRemoteGroupSearch)
            @if($groupSearchResults->isEmpty())
                <div class="card">
                    <div class="card-left">
                        <i class="fas fa-circle-info icon-folder"></i>
                    </div>
                    <div class="card-center">
                        <div class="title">
                            {{ $groupSearchType === 'goods' ? 'No se encontraron bienes.' : 'No se encontraron inventarios.' }}
                        </div>
                    </div>
                </div>
            @else
                <div class="card-grid">
                    @foreach($groupSearchResults as $result)
                        <div class="card card-item">
                            <div class="card-left">
                                <i class="fas {{ $result['type'] === 'good' ? 'fa-box' : 'fa-folder' }} icon-folder"></i>
                            </div>

                            <div class="card-center">
                                <div class="title name-item">{{ $result['title'] }}</div>
                                <div class="stats">
                                    <span class="stat-item">
                                        <i class="fas fa-filter"></i>
                                        {{ $result['type'] === 'good' ? 'Bien' : 'Inventario' }}
                                    </span>

                                    @if($result['type'] === 'good')
                                        <span class="stat-item">
                                            <i class="fas fa-folder"></i>
                                            Inventario: {{ $result['inventory_name'] }}
                                        </span>
                                    @endif

                                    <span class="stat-item">
                                        <i class="fas fa-layer-group"></i>
                                        Grupo: {{ $result['group_name'] }}
                                    </span>

                                    @if(! empty($result['sede_name']))
                                        <span class="stat-item">
                                            <i class="fas fa-building"></i>
                                            Sede: {{ $result['sede_name'] }}
                                        </span>
                                    @endif

                                    @if(! empty($result['asset_type']))
                                        <span class="stat-item">
                                            <i class="fas fa-tag"></i>
                                            Tipo: {{ $result['asset_type'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="card-right">
                                <a class="btn-open" href="{{ $result['url'] }}">
                                    <i class="fas fa-arrow-right"></i> Ir
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

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

    <div data-group-listing class="{{ $isRemoteGroupSearch ? 'hidden' : '' }}">
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
                                    <div class="card card-item" data-group-card>
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
                                                onclick="loadContent('{{ route('portal.switch', 
                                                ['slug' => $sedeData['tenant_slug'], 'redirect' => '/group/' . $group->id, 'inplace' => 1]) }}', 
                                                { updateHistory: false, onSuccess: () => initInventoryFunctions() })"
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
                    data-group-card
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
    </div>

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
