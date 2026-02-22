<?php

/**
 * Tests de acceso a la API de Tareas para el rol CONSULTOR y usuarios no autenticados.
 *
 * El consultor no tiene interfaz de tareas en la vista (verificado en
 * ConsultorHomeViewTest), pero las rutas API solo protegen con 'auth'.
 * Estos tests documentan ese comportamiento y aseguran que usuarios
 * no autenticados reciban 401 en todos los endpoints de tareas.
 */

use App\Models\Task;

describe('Acceso a la API de Tareas - No autenticado', function () {

    it('no puede crear una tarea (401)', function () {
        $this->postJson(route('tasks.store'), [
            'name' => 'Intento sin auth',
            'date' => now()->addDays(2)->toDateString(),
        ])->assertStatus(401);
    });

    it('no puede actualizar una tarea (401)', function () {
        $admin = adminUser();
        $task  = crearTareaPendiente($admin);

        $this->putJson(route('tasks.update'), [
            'id'   => $task->id,
            'name' => 'Intento sin auth',
            'date' => now()->addDays(2)->toDateString(),
        ])->assertStatus(401);
    });

    it('no puede eliminar una tarea (401)', function () {
        $admin = adminUser();
        $task  = crearTareaPendiente($admin);

        $this->deleteJson("/api/tasks/delete/{$task->id}")
            ->assertStatus(401);
    });

    it('no puede hacer toggle de una tarea (401)', function () {
        $admin = adminUser();
        $task  = crearTareaPendiente($admin);

        $this->patchJson('/api/tasks/toggle', ['id' => $task->id])
            ->assertStatus(401);
    });

    it('es redirigido al login al intentar acceder a /home', function () {
        $this->get(route('home.index'))
            ->assertRedirect('/login');
    });
});

describe('Vista de /home - Consultor (sin módulo de tareas)', function () {

    it('el consultor no ve ningún elemento del módulo de tareas en la vista', function () {
        $consultor = consultorUser();

        $response = $this->actingAs($consultor)
            ->get(route('home.index'))
            ->assertStatus(200);

        // Secciones del panel de tareas
        $response->assertDontSee('Tareas pendientes');
        $response->assertDontSee('Tareas completadas');

        // Botones de acción de tareas
        $response->assertDontSee('add-task-button');
        $response->assertDontSee('task-trash-button');
        $response->assertDontSee('task-checkbox');
        $response->assertDontSee('btnEditTask');
    });

    it('el consultor no ve tareas creadas por el administrador', function () {
        $admin     = adminUser();
        $consultor = consultorUser();

        crearTareaPendiente($admin, ['name' => 'Tarea secreta del admin']);

        $this->actingAs($consultor)
            ->get(route('home.index'))
            ->assertStatus(200)
            ->assertDontSee('Tarea secreta del admin');
    });

    it('el consultor no ve colores de semáforo de tareas (#dc3545 / #ffc107 / #28a745)', function () {
        $admin     = adminUser();
        $consultor = consultorUser();

        // Crear tareas con cada color posible
        Task::create([
            'name'    => 'Vencida',
            'date'    => now()->subDay()->toDateString(),
            'status'  => 'pending',
            'user_id' => $admin->id,
        ]);
        crearTareaPendiente($admin, ['date' => now()->addDays(2)->toDateString()]);
        crearTareaPendiente($admin, ['date' => now()->addDays(7)->toDateString()]);

        $response = $this->actingAs($consultor)->get(route('home.index'));

        $response->assertStatus(200)
            ->assertDontSee('#dc3545')
            ->assertDontSee('#ffc107')
            ->assertDontSee('#28a745');
    });
});
