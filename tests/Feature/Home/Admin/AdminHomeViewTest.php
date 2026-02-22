<?php

/**
 * Tests de vista /home para el rol ADMINISTRADOR.
 *
 * Verifica que el administrador pueda ver el panel completo de tareas:
 * sección pendientes, sección completadas, botón de agregar, botón de
 * eliminar y el trigger de edición en cada tarjeta.
 */

use App\Models\Task;

describe('Vista Home - Administrador', function () {

    // ──────────────────────────────────────────────
    // Acceso y estructura general
    // ──────────────────────────────────────────────

    it('puede acceder a /home con código 200', function () {
        $this->actingAs(adminUser())
            ->get(route('home.index'))
            ->assertStatus(200);
    });

    it('ve el saludo de bienvenida con su nombre', function () {
        $admin = adminUser();

        $this->actingAs($admin)
            ->get(route('home.index'))
            ->assertSee('¡Bienvenido')
            ->assertSee($admin->name);
    });

    it('ve la sección "Tareas pendientes"', function () {
        $this->actingAs(adminUser())
            ->get(route('home.index'))
            ->assertSee('Tareas pendientes');
    });

    it('ve la sección "Tareas completadas"', function () {
        $this->actingAs(adminUser())
            ->get(route('home.index'))
            ->assertSee('Tareas completadas');
    });

    // ──────────────────────────────────────────────
    // Botones de acción
    // ──────────────────────────────────────────────

    it('ve el botón para agregar tareas (add-task-button)', function () {
        $this->actingAs(adminUser())
            ->get(route('home.index'))
            ->assertSee('add-task-button');
    });

    it('ve el botón de eliminar tarea cuando existen tareas pendientes', function () {
        $admin = adminUser();
        crearTareaPendiente($admin);

        $this->actingAs($admin)
            ->get(route('home.index'))
            ->assertSee('task-trash-button');
    });

    it('ve el botón de marcar tarea (task-checkbox) cuando existen tareas pendientes', function () {
        $admin = adminUser();
        crearTareaPendiente($admin);

        $this->actingAs($admin)
            ->get(route('home.index'))
            ->assertSee('task-checkbox');
    });

    it('ve el trigger de edición (btnEditTask) en cada tarjeta pendiente', function () {
        $admin = adminUser();
        crearTareaPendiente($admin, ['name' => 'Tarea editable']);

        $this->actingAs($admin)
            ->get(route('home.index'))
            ->assertSee('btnEditTask');
    });

    // ──────────────────────────────────────────────
    // Contenido de tareas
    // ──────────────────────────────────────────────

    it('ve el nombre de una tarea pendiente en la lista', function () {
        $admin = adminUser();
        crearTareaPendiente($admin, ['name' => 'Revisar inventario bodega']);

        $this->actingAs($admin)
            ->get(route('home.index'))
            ->assertSee('Revisar inventario bodega');
    });

    it('ve el nombre de una tarea completada en la lista', function () {
        $admin = adminUser();
        crearTareaCompletada($admin, ['name' => 'Entregar reporte mensual']);

        $this->actingAs($admin)
            ->get(route('home.index'))
            ->assertSee('Entregar reporte mensual');
    });

    it('ve la descripción de una tarea si tiene contenido', function () {
        $admin = adminUser();
        crearTareaPendiente($admin, ['description' => 'Descripción detallada de la tarea']);

        $this->actingAs($admin)
            ->get(route('home.index'))
            ->assertSee('Descripción detallada de la tarea');
    });

    // ──────────────────────────────────────────────
    // Estado vacío
    // ──────────────────────────────────────────────

    it('ve el mensaje "No tienes tareas pendientes." cuando no hay pendientes', function () {
        $this->actingAs(adminUser())
            ->get(route('home.index'))
            ->assertSee('No tienes tareas pendientes.');
    });

    it('ve el mensaje "No tienes tareas completadas." cuando no hay completadas', function () {
        $this->actingAs(adminUser())
            ->get(route('home.index'))
            ->assertSee('No tienes tareas completadas.');
    });

    // ──────────────────────────────────────────────
    // NO ve contenido de consultor
    // ──────────────────────────────────────────────

    it('NO ve la sección "Información del Consultor"', function () {
        $this->actingAs(adminUser())
            ->get(route('home.index'))
            ->assertDontSee('Información del Consultor');
    });
});
