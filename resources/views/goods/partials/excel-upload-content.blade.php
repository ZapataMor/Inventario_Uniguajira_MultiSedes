<div class="content space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
            <h3 class="text-2xl font-semibold text-slate-800">Cargar bienes al catalogo desde Excel</h3>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <button
                type="button"
                title="Descargar plantilla Excel"
                onclick="window.location.href='{{ route('goods.download-template') }}'"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100 hover:ring-emerald-300"
            >
                <i class="fas fa-download text-sm"></i>
            </button>
            <button
                type="button"
                title="Volver a la lista de bienes"
                onclick="loadContent('{{ route('goods.index') }}', { onSuccess: () => initFormsBien() })"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-200 hover:ring-slate-300"
            >
                <i class="fas fa-arrow-left text-sm"></i>
            </button>
        </div>
    </div>

    {{-- Descripción --}}
    <div class="space-y-2">
        <p class="max-w-4xl rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
            La plantilla del modulo <b>Bienes</b> solo admite dos columnas: <b>Nombre</b> y <b>Tipo</b>.
            El tipo puede escribirse como <b>Cantidad</b> o <b>Serial</b> sin importar mayusculas o minusculas.
        </p>
        <p class="max-w-4xl rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
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
        title="Previsualizacion de datos"
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
