<?php

/**
 * Tests de vista /home para el rol CONSULTOR.
 *
 * Verifica que el consultor únicamente vea su mensaje de bienvenida
 * y la sección de información, sin ningún elemento del módulo de tareas.
 */

describe('Vista Home - Consultor', function () {

    // ──────────────────────────────────────────────
    // Acceso general
    // ──────────────────────────────────────────────

    it('puede acceder a /home con código 200', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertStatus(200);
    });

    it('ve el saludo de bienvenida con su nombre', function () {
        $consultor = consultorUser();

        $this->actingAs($consultor)
            ->get(route('home.index'))
            ->assertSee('¡Bienvenido')
            ->assertSee($consultor->name);
    });

    // ──────────────────────────────────────────────
    // Contenido exclusivo del consultor
    // ──────────────────────────────────────────────

    it('ve la sección "Información del Consultor"', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertSee('Información del Consultor');
    });

    // ──────────────────────────────────────────────
    // NO ve elementos del módulo de tareas
    // ──────────────────────────────────────────────

    it('NO ve la sección "Tareas pendientes"', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertDontSee('Tareas pendientes');
    });

    it('NO ve la sección "Tareas completadas"', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertDontSee('Tareas completadas');
    });

    it('NO ve el botón para agregar tareas (add-task-button)', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertDontSee('add-task-button');
    });

    it('NO ve el botón de eliminar tarea (task-trash-button)', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertDontSee('task-trash-button');
    });

    it('NO ve el checkbox de tarea (task-checkbox)', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertDontSee('task-checkbox');
    });

    it('NO ve el trigger de edición de tareas (btnEditTask)', function () {
        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertDontSee('btnEditTask');
    });

    it('NO ve tareas aunque existan en la base de datos', function () {
        $admin = adminUser();
        crearTareaPendiente($admin, ['name' => 'Tarea visible solo para admin']);

        $this->actingAs(consultorUser())
            ->get(route('home.index'))
            ->assertDontSee('Tarea visible solo para admin')
            ->assertDontSee('task-checkbox');
    });

    // ──────────────────────────────────────────────
    // Redirección sin autenticación
    // ──────────────────────────────────────────────

    it('un usuario no autenticado es redirigido al login', function () {
        $this->get(route('home.index'))
            ->assertRedirect('/login');
    });
});
