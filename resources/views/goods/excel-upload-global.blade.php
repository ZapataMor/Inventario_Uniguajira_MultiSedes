@extends('layouts.app')

@section('title', 'Carga Global con Inventario')

@section('content')
<div class="content">

    <div class="goods-header">
        <h3>Cargar bienes al catalogo y asignar a inventarios</h3>

        <div class="flex gap-5">
            <label class="excel-upload-btn" title="Descargar plantilla"
                onclick="globalExcelDescargarPlantilla()">
                <i class="fas fa-download"></i>
            </label>
            <label class="excel-upload-btn" title="Volver a bienes"
                onclick="loadContent('/goods', { onSuccess: () => { if (typeof initFormsBien === 'function') initFormsBien(); } })">
                <i class="fas fa-arrow-left"></i>
            </label>
        </div>
    </div>

    <p style="color:#555; font-size:0.9rem; margin-bottom:1rem;">
        Sube un Excel con los bienes. Si la columna <b>Localizacion</b> tiene el nombre
        de un inventario, el bien se asignara automaticamente a ese inventario.
        El nombre es <b>case-insensitive</b> (mayusculas/minusculas no importan).
    </p>

    <x-excel-upload-area
        area-id="global-excel-upload-area"
        input-id="globalExcelFileInput"
        accept=".xlsx,.xls"
        prompt="Arrastra y suelta un archivo aqui o haz clic para seleccionar"
        button-text="Seleccionar archivo"
    />

    <x-excel-preview-table
        title="Previsualizacion de datos"
        container-id="global-excel-preview-table"
        table-id="globalPreviewTable"
        body-id="globalPreviewBody"
        :columns="[
            ['label' => 'Bien'],
            ['label' => 'Tipo'],
            ['label' => 'Localizacion'],
            ['label' => 'Serial'],
            ['label' => 'Cantidad'],
            ['label' => 'Marca'],
            ['label' => 'Modelo'],
            ['label' => 'Estado'],
            ['label' => ''],
        ]"
        clear-button-id="btnLimpiarExcelGlobal"
        submit-button-id="btnEnviarExcelGlobal"
        error-list-id="globalErrorList"
        error-items-id="globalErrorItems"
        error-title="Advertencias:"
    />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initFormsGlobalExcel === 'function') {
                    initFormsGlobalExcel();
                }
            });
        </script>
    @endonce

</div>
@endsection
