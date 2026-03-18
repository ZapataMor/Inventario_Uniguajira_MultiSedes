<div class="content space-y-8">

    <div class="goods-header">
        <h3>Cargar bienes al catalogo desde Excel</h3>

        <div class="flex gap-5">
            <label class="excel-upload-btn" title="Descargar plantilla Excel"
                onclick="window.location.href='{{ route('goods.download-template') }}'">
                <i class="fas fa-download"></i>
            </label>
            <label class="excel-upload-btn" title="Volver a la lista de bienes"
                onclick="loadContent('{{ route('goods.index') }}', { onSuccess: () => initFormsBien() })">
                <i class="fas fa-arrow-left"></i>
            </label>
        </div>
    </div>

    <div class="space-y-3">
        <p class="max-w-4xl rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm leading-6 text-slate-600 shadow-sm">
            La plantilla del modulo <b>Bienes</b> solo admite dos columnas: <b>Nombre</b> y <b>Tipo</b>.
            El tipo puede escribirse como <b>Cantidad</b> o <b>Serial</b> sin importar mayusculas o minusculas.
        </p>
        <p class="max-w-4xl rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm leading-6 text-amber-800 shadow-sm">
            Si alguna fila viene incompleta o con un tipo invalido, se mostrara en la previsualizacion para que
            puedas corregirla o descartarla antes de enviar.
        </p>
    </div>

    <x-excel-upload-area
        area-id="excel-upload-area"
        input-id="excelFileInput"
        accept=".xlsx,.xls,.csv"
        prompt="Arrastra y suelta un archivo aqui o haz clic para seleccionar"
        button-text="Seleccionar archivo"
    />

    <x-excel-preview-table
        title="Previsualización de datos"
        container-id="excel-preview-table"
        table-id="goodsPreviewTable"
        body-id="excel-preview-body"
        :columns="[
            ['label' => 'Nombre'],
            ['label' => 'Tipo'],
            ['label' => 'Estado'],
            ['label' => ''],
        ]"
        clear-button-id="btnLimpiarExcel"
        submit-button-id="btnEnviarExcel"
        error-list-id="goodsErrorList"
        error-items-id="goodsErrorItems"
        error-title="Observaciones:"
        wrapper-class="mt-0"
    />

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof initGoodsExcelUpload === 'function') {
                    initGoodsExcelUpload();
                }
            });
        </script>
    @endonce

</div>
