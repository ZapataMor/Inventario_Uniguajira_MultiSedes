@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<div class="content">
    <h1>¡Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}!</h1>

    @if(Auth::user()->role === 'administrador')
        <div class="tasks-header tasks-header-pending" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="section-title">Tareas pendientes</h2>
            <button class="add-task-button" onclick="mostrarModal('#taskModal')">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <div class="tasks-flex">
            @forelse($dataTasks['pendientes'] as $task)
                @php
                    // Calcular días de diferencia entre la fecha de la tarea y hoy
                    $today = \Carbon\Carbon::today();
                    $taskDate = \Carbon\Carbon::parse($task->date);
                    $daysUntilDue = $today->diffInDays($taskDate, false);
                    
                    // Determinar el color según los días restantes
                    if ($daysUntilDue < 0) {
                        // Tarea vencida
                        $dateColor = 'color: #dc3545; font-weight: 600;'; // Rojo
                    } elseif ($daysUntilDue > 3) {
                        // Más de 3 días
                        $dateColor = 'color: #28a745; font-weight: 600;'; // Verde
                    } else {
                        // Entre 0 y 3 días
                        $dateColor = 'color: #ffc107; font-weight: 600;'; // Amarillo
                    }
                @endphp
                
                <div class="task-card">
                    <button class="task-checkbox" onclick="toggleTask({{ $task->id }}, this)">✓</button>
                    <div class="task-content"
                        onclick="btnEditTask('{{ $task->id }}', '{{ $task->name }}', '{{ $task->description }}', '{{ $task->date }}')">
                        <h3 class="task-title">{{ $task->name }}</h3>
                        @if(!empty($task->description))
                            <p class="task-description">{{ $task->description }}</p>
                        @endif
                    </div>
                    <div class="task-footer">
                        <span class="task-date" style="{{ $dateColor }}">
                            {{ \Carbon\Carbon::parse($task->date)->format('d M') }}
                        </span>
                        <button class="task-trash-button" onclick="deleteTask({{ $task->id }})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="no-tasks-message">
                    <i class="fas fa-clipboard-list fa-3x" style="opacity: 0.6; color: #888;"></i>
                    <p>No tienes tareas pendientes.</p>
                </div>
            @endforelse
        </div>

        <div class="tasks-header tasks-header-completed" style="display: flex; align-items: center; gap: 8px;" onclick="toggleCompletedTasks()">
            <i class="fas fa-chevron-down toggle-arrow" id="completedTasksArrow"></i>
            <h2 class="section-title">Tareas completadas</h2>
        </div>

        <div class="tasks-flex completed-tasks collapsible">
            @forelse($dataTasks['completadas'] as $task)
                <div class="task-card completed">
                    <button class="task-checkbox completed" onclick="toggleTask({{ $task->id }}, this)">✓</button>
                    <div class="task-content"
                        onclick="btnEditTask('{{ $task->id }}', '{{ $task->name }}', '{{ $task->description }}', '{{ $task->date }}')">
                        <h3 class="task-title">{{ $task->name }}</h3>
                        @if(!empty($task->description))
                            <p class="task-description">{{ $task->description }}</p>
                        @endif
                    </div>
                    <div class="task-footer">
                        <span class="task-date">{{ \Carbon\Carbon::parse($task->date)->format('d M') }}</span>
                        <button class="task-trash-button" onclick="deleteTask({{ $task->id }})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="no-tasks-message">
                    <i class="fas fa-box-open fa-3x"></i>
                    <p>No tienes tareas completadas.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>

<x-modal.tasks.task mode="create" />
<x-modal.tasks.task mode="edit" />

@endsection