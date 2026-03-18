@props([
    'areaId',
    'inputId',
    'accept' => '.xlsx,.xls',
    'prompt' => 'Arrastra y suelta un archivo aqui o haz clic para seleccionar',
    'buttonText' => 'Seleccionar archivo',
    'iconClass' => 'fas fa-file-excel fa-2x',
    'multiple' => false,
])

<div id="{{ $areaId }}" class="excel-upload-area"
    style="border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 8px; cursor: pointer;">
    <i class="{{ $iconClass }}" style="color: #1B5E20; margin-bottom: 8px;"></i>
    <p>{{ $prompt }}</p>
    <input
        type="file"
        id="{{ $inputId }}"
        accept="{{ $accept }}"
        class="hidden"
        @if($multiple) multiple @endif
    />
    <button type="button" class="select-btn" data-excel-select-trigger="true">
        {{ $buttonText }}
    </button>
</div>
