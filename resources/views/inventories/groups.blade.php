@extends('layouts.app')

@section('title', 'Grupos de Inventario')

@section('content')
<div class="content">

    <div class="inventory-header">
        <h1>Inventario</h1>
    </div>

    <div id="groups">
        <h2 class="location">Grupos</h2>

        {{-- Top bar --}}
        <div class="top-bar">
            <div class="search-container">
                <input
                    id="searchGroup"
                    class="search-bar searchInput"
                    type="text"
                    placeholder="Buscar o agregar grupos..."
                />
                <i class="search-icon fas fa-search"></i>
            </div>

            @if(Auth::user()->role === 'administrador')
                <button
                    id="btnCrearGrupo"
                    class="create-btn"
                    onclick="mostrarModal('#modalCrearGrupo')"
                >
                    Crear
                </button>
            @endif
        </div>

        {{-- Barra de control para selección múltiple --}}
        @if(Auth::user()->role === 'administrador')
        <div id="control-bar-group" class="control-bar hidden">
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

        {{-- Grid de tarjetas --}}
        <div class="card-grid">
            @if($groups->isEmpty())

                <div class="empty-state">
                    <i class="fas fa-layer-group fa-3x"></i>
                    <p>No hay grupos disponibles</p>
                </div>

            @else
                @foreach ($groups as $group)
                    <div
                        class="card card-item"
                        @if(Auth::user()->role === 'administrador')
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
                            <div id="group-name{{ $group->id }}"
                                 class="title name-item">
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
                                onclick="abrirGrupo({{ $group->id }}); event.stopPropagation();"
                            >
                                <i class="fas fa-external-link-alt"></i> Abrir
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

    </div>
</div>

{{-- MODALES --}}
<x-modal.group mode="create" />
<x-modal.group mode="rename" />

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            iniciarBusqueda('searchGroup');
        });
    </script>
@endonce

@endsection
