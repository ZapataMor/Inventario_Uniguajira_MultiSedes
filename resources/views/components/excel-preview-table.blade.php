@props([
    'title' => 'Previsualizacion',
    'containerId',
    'tableId',
    'bodyId',
    'columns' => [],
    'clearButtonId' => null,
    'clearButtonText' => 'Limpiar',
    'submitButtonId' => null,
    'submitButtonText' => 'Enviar',
    'errorListId' => null,
    'errorItemsId' => null,
    'errorTitle' => 'Errores',
    'wrapperClass' => 'mt-8',
])

@php
    $resolvedColumns = collect($columns)->map(function ($column) {
        return is_array($column) ? $column : ['label' => $column];
    });
@endphp

<section class="{{ $wrapperClass }}">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

        {{-- Cabecera --}}
        <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-4">

                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Revision</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-semibold text-slate-800">{{ $title }}</h3>
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                            Editable
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Revisa los datos, corrige celdas si hace falta y luego confirma el envio.
                    </p>
                </div>

                @if($clearButtonId || $submitButtonId)
                    <div class="flex items-center gap-2">
                        @if($clearButtonId)
                            <button
                                id="{{ $clearButtonId }}"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                <i class="fas fa-times text-xs"></i>
                                {{ $clearButtonText }}
                            </button>
                        @endif

                        @if($submitButtonId)
                            <button
                                id="{{ $submitButtonId }}"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-200 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none"
                                disabled
                            >
                                <i class="fas fa-paper-plane text-xs"></i>
                                {{ $submitButtonText }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Tabla --}}
        <div id="{{ $containerId }}">
            <div class="overflow-x-auto">
                <table id="{{ $tableId }}" class="hidden w-full min-w-[820px] divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-800">
                        <tr>
                        @foreach($resolvedColumns as $column)
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-200">
                                {{ $column['label'] ?? '' }}
                            </th>
                        @endforeach
                        </tr>
                    </thead>
                    <tbody id="{{ $bodyId }}" class="divide-y divide-slate-100 bg-white"></tbody>
                </table>
            </div>
        </div>

        {{-- Errores --}}
        @if($errorListId && $errorItemsId)
            <div id="{{ $errorListId }}" class="m-4 hidden rounded-xl border border-rose-200 bg-rose-50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-exclamation-circle text-rose-500"></i>
                    <h4 class="text-sm font-semibold text-rose-700">{{ $errorTitle }}</h4>
                </div>
                <ul id="{{ $errorItemsId }}" class="space-y-1 text-sm text-rose-600 pl-6 list-disc"></ul>
            </div>
        @endif

    </div>
</section>
