// Inicializar formularios de tareas
function initFormsTask() {
    // Crear tarea
    inicializarFormularioAjax('#taskForm', {
        onBefore: (form) => {
            const name = form.querySelector('#taskName').value.trim();
            if (!name) {
                showNotification('El nombre de la tarea es requerido', 'error');
                return false;
            }
        },
        closeModalOnSuccess: true,
        resetOnSuccess: true,
        onSuccess: (data) => {
            if (data.success) {
                refreshTasks();
                showNotification('Tarea creada exitosamente', 'success');
            }
        }
    });

    // Editar tarea
    inicializarFormularioAjax('#editTaskForm', {
        forceMethod: 'PUT', // ← Forzamos PUT aunque el form diga POST
        contentType: 'application/json',
        customBody: (form) => ({
            id: form.querySelector('#editTaskId').value,
            name: form.querySelector('#editTaskName').value,
            description: form.querySelector('#editTaskDesc').value,
            date: form.querySelector('#editTaskDate').value
        }),
        closeModalOnSuccess: true,
        onSuccess: (data) => {
            if (data.success) {
                refreshTasks();
                showNotification('Tarea actualizada exitosamente', 'success');
            }
        }
    });
}

// Cargar datos actuales de tareas sin recargar la página
async function refreshTasks() {
    try {
        const response = await fetch(window.location.pathname, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });
        if (!response.ok) throw new Error('Error al refrescar las tareas');
        const html = await response.text();
        const main = document.getElementById('main-content');
        main.innerHTML = html;
        initFormsTask(); // reactivar formularios después de refrescar
    } catch (error) {
        console.error(error);
        showNotification('No se pudo actualizar la vista', 'error');
    }
}

// Abrir modal de edición con datos precargados
function btnEditTask(id, name, description, date) {
    document.getElementById('editTaskId').value = id;
    document.getElementById('editTaskName').value = name;
    document.getElementById('editTaskDesc').value = description;
    document.getElementById('editTaskDate').value = new Date(date).toISOString().split('T')[0];
    mostrarModal('#editTaskModal');
}

// Alternar estado (pendiente ↔ completada)
function toggleTask(taskId, button) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('/api/tasks/toggle', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token, // 👈 aquí el token
        },
        body: JSON.stringify({ id: taskId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const taskCard = button.closest('.task-card');
            taskCard.classList.toggle('completed');
            button.classList.toggle('completed');
            moveTaskToProperSection(taskCard);
            showNotification('Estado de tarea actualizado', 'success');
        } else {
            throw new Error(data.error || 'Error al actualizar el estado de la tarea');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    });
}


// Eliminar tarea
function deleteTask(taskId) {
    eliminarRegistro({
        url: `/api/tasks/delete/${taskId}`,
        onSuccess: (response) => {
            if (response.success) {
                refreshTasks();
                showNotification('Tarea eliminada correctamente', 'success');
            }
        }
    });
}

// ---------------------- UTILIDADES VISUALES ---------------------- //

function showNotification(message, type) {
    const n = document.createElement('div');
    n.className = `notification ${type}`;
    n.textContent = message;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}

function createEmptyMessage(type = 'pending') {
    const div = document.createElement('div');
    div.className = 'no-tasks-message';
    const icon = document.createElement('i');
    icon.className = type === 'pending' ? 'fas fa-clipboard-list fa-3x' : 'fas fa-folder-open fa-3x';
    icon.style.opacity = '0.6';
    icon.style.color = '#888';
    const text = document.createElement('p');
    text.textContent = type === 'pending'
        ? 'No tienes tareas pendientes.'
        : 'No tienes tareas completadas.';
    div.append(icon, text);
    return div;
}

// Mover tarjetas entre secciones
function moveTaskToProperSection(card) {
    const isCompleted = card.classList.contains('completed');
    const pending = document.querySelector('.tasks-flex:not(.completed-tasks)');
    const completed = document.querySelector('.completed-tasks');

    ['pending', 'completed'].forEach(t => {
        const container = t === 'pending' ? pending : completed;
        const msg = container.querySelector('.no-tasks-message');
        if (msg) msg.remove();
    });

    const target = isCompleted ? completed : pending;
    const source = isCompleted ? pending : completed;
    target.appendChild(card);

    if (source.children.length === 0) {
        source.appendChild(createEmptyMessage(isCompleted ? 'pending' : 'completed'));
    }
}

// Colapsar tareas completadas
function toggleCompletedTasks() {
    const section = document.querySelector('.completed-tasks');
    const arrow = document.getElementById('completedTasksArrow');
    if (section && arrow) {
        section.classList.toggle('collapsed');
        arrow.classList.toggle('rotated');
    }
}

// Inicializar al cargar la vista
document.addEventListener('DOMContentLoaded', () => {
    initFormsTask();
});
