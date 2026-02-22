<?php

/**
 * Tests de CRUD de tareas para el rol ADMINISTRADOR.
 *
 * Cubre las cuatro operaciones:
 *   - Crear  → POST  /api/tasks/store
 *   - Editar → PUT   /api/tasks/update
 *   - Marcar → PATCH /api/tasks/toggle
 *   - Borrar → DELETE /api/tasks/delete/{id}
 */

use App\Models\Task;

describe('CRUD de Tareas - Administrador', function () {

    // ══════════════════════════════════════════════
    // CREAR TAREA
    // ══════════════════════════════════════════════

    describe('Crear tarea', function () {

        it('puede crear una tarea con todos los campos', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name'        => 'Verificar equipos del laboratorio',
                    'description' => 'Revisar todos los equipos del Lab. 3',
                    'date'        => now()->addDays(5)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'name'    => 'Verificar equipos del laboratorio',
                'status'  => 'pending',
                'user_id' => $admin->id,
            ]);
        });

        it('puede crear una tarea solo con nombre y fecha (sin descripción)', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name' => 'Tarea mínima',
                    'date' => now()->addDays(2)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'name'        => 'Tarea mínima',
                'description' => null,
                'status'      => 'pending',
            ]);
        });

        it('la tarea se crea con estado "pending" por defecto', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name' => 'Nueva tarea',
                    'date' => now()->addDays(1)->toDateString(),
                ]);

            $task = Task::where('name', 'Nueva tarea')->first();
            expect($task->status)->toBe('pending');
        });

        it('la tarea se asigna al usuario autenticado', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name' => 'Tarea del admin',
                    'date' => now()->addDays(3)->toDateString(),
                ]);

            $this->assertDatabaseHas('tasks', [
                'name'    => 'Tarea del admin',
                'user_id' => $admin->id,
            ]);
        });

        it('puede crear una tarea con fecha de hoy', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name' => 'Tarea de hoy',
                    'date' => now()->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // EDITAR TAREA
    // ══════════════════════════════════════════════

    describe('Editar tarea', function () {

        it('puede actualizar el nombre de una tarea', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin, ['name' => 'Nombre original']);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'   => $task->id,
                    'name' => 'Nombre actualizado',
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'id'   => $task->id,
                'name' => 'Nombre actualizado',
            ]);
        });

        it('puede actualizar la descripción de una tarea', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin, ['description' => 'Descripción vieja']);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'          => $task->id,
                    'name'        => $task->name,
                    'description' => 'Descripción nueva y detallada',
                    'date'        => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'id'          => $task->id,
                'description' => 'Descripción nueva y detallada',
            ]);
        });

        it('puede actualizar la fecha de una tarea', function () {
            $admin      = adminUser();
            $nuevaFecha = now()->addDays(10)->toDateString();
            $task       = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'   => $task->id,
                    'name' => $task->name,
                    'date' => $nuevaFecha,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            // Comparamos usando el cast 'date' del modelo para evitar
            // diferencias de formato entre la cadena Y-m-d y el timestamp
            // que almacena MySQL (Y-m-d H:i:s).
            $fechaGuardada = Task::find($task->id)->date->toDateString();
            expect($fechaGuardada)->toBe($nuevaFecha);
        });

        it('retorna 404 al intentar editar una tarea inexistente', function () {
            $this->actingAs(adminUser())
                ->putJson(route('tasks.update'), [
                    'id'   => 99999,
                    'name' => 'No existe',
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(404);
        });
    });

    // ══════════════════════════════════════════════
    // MARCAR / DESMARCAR TAREA
    // ══════════════════════════════════════════════

    describe('Marcar / desmarcar tarea (toggle)', function () {

        it('puede marcar una tarea pendiente como completada', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->patchJson('/api/tasks/toggle', ['id' => $task->id])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'id'     => $task->id,
                'status' => 'completed',
            ]);
        });

        it('puede desmarcar una tarea completada de vuelta a pendiente', function () {
            $admin = adminUser();
            $task  = crearTareaCompletada($admin);

            $this->actingAs($admin)
                ->patchJson('/api/tasks/toggle', ['id' => $task->id])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'id'     => $task->id,
                'status' => 'pending',
            ]);
        });

        it('dos toggles consecutivos regresan la tarea a su estado original', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)->patchJson('/api/tasks/toggle', ['id' => $task->id]);
            $this->actingAs($admin)->patchJson('/api/tasks/toggle', ['id' => $task->id]);

            $this->assertDatabaseHas('tasks', [
                'id'     => $task->id,
                'status' => 'pending',
            ]);
        });

        it('retorna 404 al intentar hacer toggle de una tarea inexistente', function () {
            $this->actingAs(adminUser())
                ->patchJson('/api/tasks/toggle', ['id' => 99999])
                ->assertStatus(404);
        });
    });

    // ══════════════════════════════════════════════
    // ELIMINAR TAREA
    // ══════════════════════════════════════════════

    describe('Eliminar tarea', function () {

        it('puede eliminar una tarea pendiente', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->deleteJson("/api/tasks/delete/{$task->id}")
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        });

        it('puede eliminar una tarea completada', function () {
            $admin = adminUser();
            $task  = crearTareaCompletada($admin);

            $this->actingAs($admin)
                ->deleteJson("/api/tasks/delete/{$task->id}")
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        });

        it('retorna 404 al intentar eliminar una tarea inexistente', function () {
            $this->actingAs(adminUser())
                ->deleteJson('/api/tasks/delete/99999')
                ->assertStatus(404);
        });

        it('no elimina otras tareas al eliminar una específica', function () {
            $admin  = adminUser();
            $task1  = crearTareaPendiente($admin, ['name' => 'Tarea que se queda']);
            $task2  = crearTareaPendiente($admin, ['name' => 'Tarea que se borra']);

            $this->actingAs($admin)
                ->deleteJson("/api/tasks/delete/{$task2->id}")
                ->assertStatus(200);

            $this->assertDatabaseHas('tasks', ['id' => $task1->id]);
            $this->assertDatabaseMissing('tasks', ['id' => $task2->id]);
        });
    });
});
