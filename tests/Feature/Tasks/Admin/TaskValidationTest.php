<?php

/**
 * Tests de validación del módulo de Tareas.
 *
 * Cubre las reglas definidas en TaskController::store() y ::update():
 *   - name:  required | string | max:255
 *   - date:  required | date   | after_or_equal:today
 *   - description: nullable | string
 *
 * También verifica la lógica de colores de fecha en la vista:
 *   - Vencida    (daysUntilDue < 0)  → rojo    #dc3545
 *   - Próxima    (0 ≤ días ≤ 3)      → amarillo #ffc107
 *   - Con tiempo (días > 3)          → verde   #28a745
 */

use App\Models\Task;

describe('Validaciones de Tareas', function () {

    // ══════════════════════════════════════════════
    // VALIDACIONES DE NOMBRE
    // ══════════════════════════════════════════════

    describe('Campo: nombre (name)', function () {

        it('es obligatorio al crear una tarea', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => '',
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('es obligatorio al actualizar una tarea', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'   => $task->id,
                    'name' => '',
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('no puede superar 255 caracteres al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => str_repeat('A', 256),
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('no puede superar 255 caracteres al actualizar', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'   => $task->id,
                    'name' => str_repeat('Z', 256),
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('acepta exactamente 255 caracteres', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => str_repeat('B', 255),
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('acepta un nombre corto de 1 carácter', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'X',
                    'date' => now()->addDays(1)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // VALIDACIONES DE FECHA
    // ══════════════════════════════════════════════

    describe('Campo: fecha (date)', function () {

        it('es obligatoria al crear una tarea', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'Sin fecha',
                    'date' => '',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
        });

        it('es obligatoria al actualizar una tarea', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'   => $task->id,
                    'name' => $task->name,
                    'date' => '',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
        });

        it('no puede ser una fecha pasada al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'Tarea vencida',
                    'date' => now()->subDay()->toDateString(),
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
        });

        it('no puede ser una fecha pasada al actualizar', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'   => $task->id,
                    'name' => $task->name,
                    'date' => now()->subDays(5)->toDateString(),
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
        });

        it('acepta la fecha de hoy al crear (after_or_equal:today)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'Tarea de hoy',
                    'date' => now()->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('acepta la fecha de hoy al actualizar', function () {
            $admin = adminUser();
            $task  = crearTareaPendiente($admin);

            $this->actingAs($admin)
                ->putJson(route('tasks.update'), [
                    'id'   => $task->id,
                    'name' => $task->name,
                    'date' => now()->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('acepta una fecha futura al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'Tarea del futuro',
                    'date' => now()->addDays(30)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('el mensaje de error por fecha pasada está en español', function () {
            $response = $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'Tarea',
                    'date' => now()->subDay()->toDateString(),
                ]);

            $response->assertStatus(422);

            $errors = $response->json('errors.date');
            expect($errors)->toContain(
                'La fecha de la tarea no puede ser anterior al día de hoy.'
            );
        });

        it('no acepta un formato de fecha inválido', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'Fecha inválida',
                    'date' => 'no-es-una-fecha',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
        });
    });

    // ══════════════════════════════════════════════
    // VALIDACIONES DE DESCRIPCIÓN
    // ══════════════════════════════════════════════

    describe('Campo: descripción (description)', function () {

        it('es opcional al crear (puede omitirse)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('tasks.store'), [
                    'name' => 'Solo nombre y fecha',
                    'date' => now()->addDays(2)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('puede ser null explícitamente', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name'        => 'Descripción nula',
                    'description' => null,
                    'date'        => now()->addDays(2)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'name'        => 'Descripción nula',
                'description' => null,
            ]);
        });

        it('puede tener contenido de texto', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name'        => 'Con descripción',
                    'description' => 'Esta es una descripción válida.',
                    'date'        => now()->addDays(2)->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('tasks', [
                'name'        => 'Con descripción',
                'description' => 'Esta es una descripción válida.',
            ]);
        });
    });

    // ══════════════════════════════════════════════
    // COLORES DE FECHA EN LA VISTA
    // Regla del blade:
    //   daysUntilDue < 0  → #dc3545 (rojo)    — vencida
    //   daysUntilDue > 3  → #28a745 (verde)   — con tiempo
    //   0 ≤ días ≤ 3      → #ffc107 (amarillo) — próxima
    // ══════════════════════════════════════════════

    describe('Colores de fecha (semáforo)', function () {

        it('tarea vencida muestra color rojo (#dc3545)', function () {
            $admin = adminUser();

            // Se inserta directamente en DB para saltar la validación API
            Task::create([
                'name'    => 'Tarea vencida hace 2 días',
                'date'    => now()->subDays(2)->toDateString(),
                'status'  => 'pending',
                'user_id' => $admin->id,
            ]);

            $this->actingAs($admin)
                ->get(route('home.index'))
                ->assertStatus(200)
                ->assertSee('#dc3545');
        });

        it('tarea con más de 3 días muestra color verde (#28a745)', function () {
            $admin = adminUser();
            crearTareaPendiente($admin, ['date' => now()->addDays(10)->toDateString()]);

            $this->actingAs($admin)
                ->get(route('home.index'))
                ->assertStatus(200)
                ->assertSee('#28a745');
        });

        it('tarea que vence en exactamente 4 días muestra color verde (#28a745)', function () {
            $admin = adminUser();
            crearTareaPendiente($admin, ['date' => now()->addDays(4)->toDateString()]);

            $this->actingAs($admin)
                ->get(route('home.index'))
                ->assertStatus(200)
                ->assertSee('#28a745');
        });

        it('tarea que vence en exactamente 3 días muestra color amarillo (#ffc107)', function () {
            $admin = adminUser();
            crearTareaPendiente($admin, ['date' => now()->addDays(3)->toDateString()]);

            $this->actingAs($admin)
                ->get(route('home.index'))
                ->assertStatus(200)
                ->assertSee('#ffc107');
        });

        it('tarea que vence en 1 día muestra color amarillo (#ffc107)', function () {
            $admin = adminUser();
            crearTareaPendiente($admin, ['date' => now()->addDay()->toDateString()]);

            $this->actingAs($admin)
                ->get(route('home.index'))
                ->assertStatus(200)
                ->assertSee('#ffc107');
        });

        it('tarea que vence hoy muestra color amarillo (#ffc107)', function () {
            $admin = adminUser();
            crearTareaPendiente($admin, ['date' => now()->toDateString()]);

            $this->actingAs($admin)
                ->get(route('home.index'))
                ->assertStatus(200)
                ->assertSee('#ffc107');
        });

        it('tres tareas simultáneas muestran los tres colores del semáforo', function () {
            $admin = adminUser();

            // Vencida → rojo
            Task::create([
                'name'    => 'Vencida',
                'date'    => now()->subDays(1)->toDateString(),
                'status'  => 'pending',
                'user_id' => $admin->id,
            ]);

            // Próxima (2 días) → amarillo
            crearTareaPendiente($admin, ['date' => now()->addDays(2)->toDateString()]);

            // Con tiempo (7 días) → verde
            crearTareaPendiente($admin, ['date' => now()->addDays(7)->toDateString()]);

            $response = $this->actingAs($admin)->get(route('home.index'));

            $response->assertStatus(200)
                ->assertSee('#dc3545')
                ->assertSee('#ffc107')
                ->assertSee('#28a745');
        });
    });
});
