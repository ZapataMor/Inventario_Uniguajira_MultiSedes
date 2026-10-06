@extends('layouts.app')

@section('title', 'Carga Masiva de Bienes')

@section('content')
<div class="content excel-page">

    <div class="excel-page-header">
        <div>
            <h3 class="excel-page-title">Carga masiva de bienes</h3>
            <span id="inventory-name" class="excel-page-subtitle" data-id="{{ $inventory->id }}" data-group-id="{{ $inventory->group_id }}">
                <i class="fas fa-layer-group"></i>
                {{ $inventory->name }}
            </span>
            @if ($inventory->responsible)
                <p class="excel-page-meta">
                    <i class="fas fa-user"></i>
                    Responsable: {{ $inventory->responsible }}
                </p>
            @endif
        </div>

        <div class="excel-page-actions">
            <button type="button" title="Descargar plantilla Excel" onclick="descargarPlantillaInventario()" class="excel-page-action-btn">
                <i class="fas fa-download"></i>
            </button>
            <button type="button" title="Volver al inventario" onclick="loadContent('{{ route('inventory.goods', ['groupId' => $inventory->group_id, 'inventoryId' => $inventory->id]) }}', { onSuccess: () => initGoodsInventoryFunctions() })" class="excel-page-action-btn">
                <i class="fas fa-arrow-left"></i>
            </button>
        </div>
    </div>

    <p class="excel-page-note">
        Sube un archivo Excel con los bienes a agregar a este inventario.
        Los bienes que no existan en el catalogo seran creados automaticamente.
    </p>

    <x-excel-upload-area
        area-id="inv-excel-upload-area"
        input-id="invExcelFileInput"
        accept=".xlsx,.xls"
        prompt="Arrastra y suelta un archivo aqui o haz clic para seleccionar"
        button-text="Seleccionar archivo"
    />

    <x-excel-preview-table
        title="Previsualización"
        container-id="inv-excel-preview-table"
        table-id="invPreviewTable"
        body-id="invPreviewBody"
        :columns="[
            ['label' => 'Bien'],
            ['label' => 'Tipo'],
            ['label' => 'Serial'],
            ['label' => 'Cantidad'],
            ['label' => 'Marca'],
            ['label' => 'Modelo'],
            ['label' => 'Estado'],
            ['label' => 'Descripción'],
            ['label' => 'Color'],
            ['label' => 'Condiciones'],
            ['label' => 'Fecha ingreso'],
            ['label' => ''],
        ]"
        clear-button-id="btnLimpiarExcelInventario"
        submit-button-id="btnEnviarExcelInventario"
        error-list-id="invErrorList"
        error-items-id="invErrorItems"
        error-title="Errores:"
        wrapper-class=""
    />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initInventoryExcelUploadView === 'function') {
                    initInventoryExcelUploadView();
                }
            });
        </script>
    @endonce

</div>
@endsection
