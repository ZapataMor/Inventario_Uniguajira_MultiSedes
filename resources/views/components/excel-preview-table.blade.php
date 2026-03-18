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
    'wrapperStyle' => 'margin-top: 1.5rem;',
])

@php
    $resolvedColumns = collect($columns)->map(function ($column) {
        return is_array($column) ? $column : ['label' => $column];
    });
@endphp

<div style="{{ $wrapperStyle }}">
    <h3>{{ $title }}</h3>
    <div id="{{ $containerId }}" style="overflow-x: auto;">
        <table id="{{ $tableId }}" class="hidden" style="width:100%; border-collapse:collapse; font-size: 0.85rem;">
            <thead>
                <tr style="background:#1B5E20; color:#fff;">
                    @foreach($resolvedColumns as $column)
                        <th style="padding:8px;{{ $column['style'] ?? '' }}">{{ $column['label'] ?? '' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="{{ $bodyId }}"></tbody>
        </table>
    </div>

    @if($clearButtonId || $submitButtonId)
        <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
            @if($clearButtonId)
                <button id="{{ $clearButtonId }}" type="button" class="btn">{{ $clearButtonText }}</button>
            @endif

            @if($submitButtonId)
                <button id="{{ $submitButtonId }}" type="button" class="btn create-btn" disabled>
                    {{ $submitButtonText }}
                </button>
            @endif
        </div>
    @endif

    @if($errorListId && $errorItemsId)
        <div id="{{ $errorListId }}" style="margin-top:1rem; display:none;">
            <h4 style="color:#b71c1c;">{{ $errorTitle }}</h4>
            <ul id="{{ $errorItemsId }}" style="color:#b71c1c; font-size:0.85rem;"></ul>
        </div>
    @endif
</div>
