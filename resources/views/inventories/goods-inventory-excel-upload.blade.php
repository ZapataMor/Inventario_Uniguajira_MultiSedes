@extends('layouts.app')

@section('title', 'Carga Masiva de Bienes')

@section('content')
<div class="content">

    <div class="goods-header">
        <div>
            <h3>Carga masiva de bienes</h3>
            <span id="inventory-name" class="location" data-id="{{ $inventory->id }}" data-group-id="{{ $inventory->group_id }}">
                {{ $inventory->name }}
            </span>
            @if ($inventory->responsible)
                <span class="sub-info">Responsable: {{ $inventory->responsible }}</span>
            @endif
        </div>

        <div class="flex gap-5">
            <label class="excel-upload-btn" title="Descargar plantilla Excel"
                onclick="descargarPlantillaInventario()">
                <i class="fas fa-download"></i>
            </label>
            <label class="excel-upload-btn" title="Volver al inventario"
                onclick="loadContent('{{ route('inventory.goods', ['groupId' => $inventory->group_id, 'inventoryId' => $inventory->id]) }}', { onSuccess: () => initGoodsInventoryFunctions() })">
                <i class="fas fa-arrow-left"></i>
            </label>
        </div>
    </div>

    <p style="color: #555; font-size: 0.9rem; margin-bottom: 1rem;">
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
        title="Previsualizacion"
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
            ['label' => ''],
        ]"
        clear-button-id="btnLimpiarExcelInventario"
        submit-button-id="btnEnviarExcelInventario"
        error-list-id="invErrorList"
        error-items-id="invErrorItems"
        error-title="Errores:"
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
