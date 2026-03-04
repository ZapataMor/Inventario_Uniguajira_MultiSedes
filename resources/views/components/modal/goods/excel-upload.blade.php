@extends('layouts.app')

@section('title', 'Subir Excel')

@section('content')
<div class="content">

    <div class="goods-header">
        <h3>Cargar datos de bienes desde Excel</h3>

        <div class="flex gap-5">
            <label class="excel-upload-btn" title="Descargar plantilla Excel"
                onclick="window.location.href='{{ route('goods.download-template') }}'">
                <i class="fas fa-download"></i>
            </label>
            <label class="excel-upload-btn" title="Volver a la lista de bienes"
                onclick="loadContent( '{{ route('goods.index') }}', { onSuccess: () => initFormsBien() } )">
                <i class="fas fa-arrow-left"></i>
            </label>
        </div>
    </div>

    <div id="excel-upload-area"
        class="excel-upload-area"
        style="border: 2px dashed #ccc; padding: 20px; text-align: center;"
    >
        <p>Arrastra y suelta un archivo aquí o haz clic para seleccionar un archivo</p>

        <input
            type="file"
            id="excelFileInput"
            accept=".xlsx, .xls, .csv"
            class="hidden"
            onchange="handleFileUpload(event)"
        />

        <button id="btn-select-excel" class="select-btn"
            onclick="document.getElementById('excelFileInput').click()">
            Seleccionar archivo
        </button>
    </div>

    <br />

    <div>
        <h3>Previsualización de datos</h3>
        <div id="excel-preview-table">
            <table class="hidden">
                <thead>
                    <tr>
                        <th>Bien</th>
                        <th>Tipo</th>
                        <th>Imagen</th>
                    </tr>
                </thead>
                <tbody id="excel-preview-body">
                </tbody>
            </table>
        </div>

        <button onclick="btnClearExcelUploadUI()" class="btn">Limpiar</button>
        <button id="btnEnviarExcel" class="btn create-btn"
            onclick="sendGoodsData()"
            disabled>
            Enviar
        </button>
    </div>

</div>
@endsection
