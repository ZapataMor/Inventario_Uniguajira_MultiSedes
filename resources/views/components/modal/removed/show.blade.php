<div class="form-section">
    <div class="section-header">Información del Bien</div>

    <div class="form-fields-grid">
        {{-- Nombre en toda la fila --}}
        <div class="form-field-full">
            <label class="form-label">Nombre del Bien:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->name }}" disabled />
        </div>

        {{-- Tipo --}}
        <div>
            <label class="form-label">Tipo:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->type }}" disabled />
        </div>

        {{-- Cantidad o Serial --}}
        @if($removedAsset->type === 'Cantidad')
            <div>
                <label class="form-label">Cantidad dada de baja:</label>
                <input type="text" class="form-input" value="{{ $removedAsset->quantity }}" disabled />
            </div>
        @else
            <div>
                <label class="form-label">Serial:</label>
                <input type="text" class="form-input" value="{{ $removedAsset->serial ?? 'N/A' }}" disabled />
            </div>
        @endif
    </div>
</div>


{{-- 2) UBICACIÓN ORIGINAL --}}
<div class="form-section">
    <div class="section-header">Ubicación Original</div>

    <div class="form-fields-grid">
        <div>
            <label class="form-label">Grupo:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->group_name }}" disabled />
        </div>

        <div>
            <label class="form-label">Inventario:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->inventory_name }}" disabled />
        </div>

        @if(!empty($removedAsset->inventory_responsible))
            <div class="form-field-full">
                <label class="form-label">Responsable del Inventario:</label>
                <input type="text" class="form-input" value="{{ $removedAsset->inventory_responsible }}" disabled />
            </div>
        @endif
    </div>
</div>


{{-- 3) DETALLES DEL EQUIPO (SOLO SERIAL) --}}
@if($removedAsset->type === 'Serial')
<div class="form-section">
    <div class="section-header">Detalles del Equipo (Serial)</div>

    <div class="form-fields-grid">
        <div>
            <label class="form-label">Marca:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->brand ?? 'N/A' }}" disabled />
        </div>

        <div>
            <label class="form-label">Modelo:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->model ?? 'N/A' }}" disabled />
        </div>

        <div>
            <label class="form-label">Estado:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->status ?? 'N/A' }}" disabled />
        </div>

        <div>
            <label class="form-label">Color:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->color ?? 'N/A' }}" disabled />
        </div>

        <div>
            <label class="form-label">Fecha de Entrada:</label>
            <input type="text" class="form-input"
                value="{{ $removedAsset->entry_date ? \Carbon\Carbon::parse($removedAsset->entry_date)->format('d/m/Y') : 'N/A' }}"
                disabled />
        </div>

        <div>
            <label class="form-label">Fecha de Salida:</label>
            <input type="text" class="form-input"
                value="{{ $removedAsset->exit_date ? \Carbon\Carbon::parse($removedAsset->exit_date)->format('d/m/Y') : 'N/A' }}"
                disabled />
        </div>

        {{-- Condiciones técnicas full --}}
        <div class="form-field-full">
            <label class="form-label">Condiciones Técnicas:</label>
            <textarea class="form-input" rows="3" disabled>{{ $removedAsset->technical_conditions ?? 'N/A' }}</textarea>
        </div>

        {{-- Descripción full --}}
        <div class="form-field-full">
            <label class="form-label">Descripción:</label>
            <textarea class="form-input" rows="3" disabled>{{ $removedAsset->description ?? 'N/A' }}</textarea>
        </div>
    </div>
</div>
@endif


{{-- 4) INFORMACIÓN DE LA BAJA --}}
<div class="form-section">
    <div class="section-header">Información de la Baja</div>

    <div class="form-fields-grid">
        {{-- Motivo full --}}
        <div class="form-field-full">
            <label class="form-label">Motivo de la Baja:</label>
            <textarea class="form-input" rows="3" disabled>{{ $removedAsset->reason ?? 'Sin motivo especificado' }}</textarea>
        </div>

        <div>
            <label class="form-label">Dado de Baja Por:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->removed_by_user ?? 'N/A' }}" disabled />
        </div>

        @if(!empty($removedAsset->user_email))
        <div>
            <label class="form-label">Email del Usuario:</label>
            <input type="text" class="form-input" value="{{ $removedAsset->user_email }}" disabled />
        </div>
        @endif

        <div>
            <label class="form-label">Fecha y Hora:</label>
            <input type="text" class="form-input"
                value="{{ \Carbon\Carbon::parse($removedAsset->created_at)->format('d/m/Y H:i:s') }}"
                disabled />
        </div>
    </div>
</div>


{{-- 5) IMAGEN --}}
@if(!empty($removedAsset->image))
<div class="form-section">
    <div class="section-header">Imagen del Bien</div>

    <div style="text-align: center; padding: 20px;">
        <img
            src="{{ asset('storage/' . $removedAsset->image) }}"
            alt="{{ $removedAsset->name }}"
            style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
            onerror="this.src='{{ asset('assets/uploads/img/goods/default.jpg') }}'"
        />
    </div>
</div>
@endif
