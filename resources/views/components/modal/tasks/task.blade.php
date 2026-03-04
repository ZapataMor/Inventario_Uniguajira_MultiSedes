@props([
    'mode' => 'create', // 'create' o 'edit'
    'id' => $mode === 'create' ? 'taskModal' : 'editTaskModal',
    'title' => $mode === 'create' ? 'Crear Nueva Tarea' : 'Editar Tarea',
    'action' => $mode === 'create' ? route('tasks.store') : route('tasks.update'),
    'method' => $mode === 'create' ? 'POST' : 'PUT',
])

<div id="{{ $id }}" class="modal">
    <div class="modal-content modal-content-medium">
        <span class="close" onclick="ocultarModal('#{{ $id }}')">&times;</span>
        <h2>{{ $title }}</h2>

        <form id="{{ $mode === 'create' ? 'taskForm' : 'editTaskForm' }}"
              autocomplete="off"
              action="{{ $action }}"
              method="POST">

            @csrf
            @if($mode === 'edit')
                @method('PUT')
                <input type="hidden" id="editTaskId" name="id">
            @endif

            <div>
                <label for="{{ $mode === 'create' ? 'taskName' : 'editTaskName' }}">Nombre:</label>
                <input type="text"
                       id="{{ $mode === 'create' ? 'taskName' : 'editTaskName' }}"
                       name="name" required>
            </div>

            <div>
                <label for="{{ $mode === 'create' ? 'taskDesc' : 'editTaskDesc' }}">Descripción:</label>
                <textarea id="{{ $mode === 'create' ? 'taskDesc' : 'editTaskDesc' }}"
                          name="description"></textarea>
            </div>

            <div>
                <label for="{{ $mode === 'create' ? 'taskDate' : 'editTaskDate' }}">Fecha:</label>
                <input type="date"
                       id="{{ $mode === 'create' ? 'taskDate' : 'editTaskDate' }}"
                       name="date"
                       min="{{ now()->format('Y-m-d') }}"
                       value="{{ now()->format('Y-m-d') }}">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn submit-btn">
                    {{ $mode === 'create' ? 'Guardar' : 'Actualizar' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 📽 Agregamos este bloque --}}
@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof initFormsTask === 'function') {
                initFormsTask();
            }
        });
    </script>
@endonce