@extends('layouts.app')

@section('title', 'Bienes')

@section('content')
<div class="content">

    <div class="goods-header">
        <h2>Catalogo de bienes</h2>

        {{-- Botón subir Excel --}}
        @if (Auth::user()->role === 'administrador')
            <label class="excel-upload-btn" title="Subir Excel"
                onclick="loadContent( '{{ route('goods.excel-upload') }}' )">
                <i class="fas fa-file-excel"></i>
            </label>
        @endif
    </div>

    <x-generals.top-bar
        id="searchGood"
        placeholder="Buscar bien..."
        modal="#modalCrearBien"
    />

    {{-- Cuando NO hay bienes --}}
    @if($dataGoods->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open fa-3x"></i>
            <p>No hay bienes disponibles</p>
        </div>

    {{-- Cuando SÍ hay bienes --}}
    @else
        <div id="bienes-grid" class="bienes-grid">

            @foreach ($dataGoods as $bien)
                <div class="bien-card card-item">

                    {{-- Imagen del bien --}}
                    <img
                        src="{{ $bien->image ? asset('storage/' . $bien->image) : asset('assets/defaults/goods/default.jpg') }}"
                        class="bien-image"
                    />

                    <div class="bien-info">
                        <h3 class="name-item">{{ $bien->name }}
                            <img
                                src="{{ asset('assets/icons/' . ($bien->type == 'Cantidad' ? 'bienCantidad.svg' : 'bienSerial.svg')) }}"
                                alt="Icono tipo bien"
                                class="bien-icon"
                            />
                        </h3>
                        <p>Cantidad: {{ $bien->total_quantity }}</p>
                    </div>

                    @if(Auth::user()->role === 'administrador')
                        <div class="actions">
                            <a class="btn-editar"
                                onclick="btnEditarBien({{ $bien->id }}, '{{ $bien->name }}')">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a class="btn-eliminar"
                                onclick="eliminarBien({{ $bien->id }})">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    @endif

                </div>
            @endforeach

        </div>
    @endif

    {{-- MODALES --}}
    @if (Auth::user()->role === 'administrador')
        <x-modal.goods.good mode="create" />
        <x-modal.goods.good mode="edit" />
    @endif

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initFormsBien === 'function') {
                    initFormsBien();
                }
            });
        </script>
    @endonce

</div>
@endsection
