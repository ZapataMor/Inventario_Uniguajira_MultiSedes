@extends('layouts.app')

@section('title', 'Carga Masiva por Localización')

@section('content')
<div class="content excel-page">

    <div class="excel-page-header">
        <div>
            <h3 class="excel-page-title">Carga masiva por localización</h3>
            <span class="excel-page-subtitle">
                <i class="fas fa-layer-group"></i>
                Distribuir bienes entre inventarios
            </span>
        </div>

        <div class="excel-page-actions">
            <button type="button" title="Descargar plantilla Excel" onclick="descargarPlantillaLocalizacion()" class="excel-page-action-btn">
                <i class="fas fa-download"></i>
            </button>
            <button type="button" title="Volver a grupos" onclick="loadContent('{{ route('inventory.groups') }}', { onSuccess: () => initGroupFunctions() })" class="excel-page-action-btn">
                <i class="fas fa-arrow-left"></i>
            </button>
        </div>
    </div>

    <p class="excel-page-note">
        Sube un archivo Excel con la columna <b>Localizacion</b> indicando el nombre exacto del inventario destino.
        Los bienes que no existan en el catálogo serán creados automáticamente.
    </p>

    <x-excel-upload-area
        area-id="loc-excel-upload-area"
        input-id="locExcelFileInput"
        accept=".xlsx,.xls"
        prompt="Arrastra y suelta un archivo aquí o haz clic para seleccionar"
        button-text="Seleccionar archivo"
    />

    <x-excel-preview-table
        title="Previsualización"
        container-id="loc-excel-preview-table"
        table-id="locPreviewTable"
        body-id="locPreviewBody"
        :columns="[
            ['label' => 'Bien'],
            ['label' => 'Tipo'],
            ['label' => 'Serial'],
            ['label' => 'Cantidad'],
            ['label' => 'Marca'],
            ['label' => 'Modelo'],
            ['label' => 'Estado'],
            ['label' => 'Localización'],
            ['label' => ''],
        ]"
        clear-button-id="btnLimpiarExcelLocalizacion"
        submit-button-id="btnEnviarExcelLocalizacion"
        error-list-id="locErrorList"
        error-items-id="locErrorItems"
        error-title="Errores:"
        wrapper-class=""
    />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initLocalizacionExcelUploadView === 'function') {
                    initLocalizacionExcelUploadView();
                }
            });
        </script>
    @endonce

</div>
@endsection
