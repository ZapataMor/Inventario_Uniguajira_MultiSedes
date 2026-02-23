<?php

/**
 * Tests de acceso al módulo de Usuarios para el rol CONSULTOR
 * y para usuarios no autenticados.
 *
 * El consultor NO tiene acceso a la vista /users ni a ningún
 * endpoint de la API de usuarios.
 *
 * Reglas verificadas:
 *   - GET  /users          → 403 para consultor, redirect para no autenticado
 *   - POST /api/users/store   → 403 para consultor, 401 para no autenticado
 *   - POST /api/users/update  → 403 para consultor, 401 para no autenticado
 *   - DELETE /api/users/delete/{id} → 403 para consultor, 401 para no autenticado
 */

use App\Models\User;

describe('Acceso a la API de Usuarios - No autenticado', function () {

    it('no puede ver la lista de usuarios (redirige al login)', function () {
        $this->get('/users')
            ->assertRedirect('/login');
    });

    it('no puede crear un usuario (401)', function () {
        $this->postJson('/api/users/store', [
            'name'     => 'Intento sin auth',
            'username' => 'sinauth',
            'email'    => 'sinauth@test.com',
            'password' => 'clave123',
            'role'     => 'consultor',
        ])->assertStatus(401);
    });

    it('no puede editar un usuario (401)', function () {
        $usuario = User::factory()->consultor()->create();

        $this->postJson('/api/users/update', [
            'id'       => $usuario->id,
            'name'     => 'Intento sin auth',
            'username' => $usuario->username,
            'email'    => $usuario->email,
            'role'     => $usuario->role,
        ])->assertStatus(401);
    });

    it('no puede eliminar un usuario (401)', function () {
        $usuario = User::factory()->consultor()->create();

        $this->deleteJson("/api/users/delete/{$usuario->id}")
            ->assertStatus(401);
    });
});

describe('Acceso al módulo de Usuarios - Consultor', function () {

    // ══════════════════════════════════════════════
    // ACCESO A LA VISTA
    // ══════════════════════════════════════════════

    describe('Vista /users', function () {

        it('el consultor no puede acceder a la vista /users (403)', function () {
            $this->actingAs(consultorUser())
                ->get('/users')
                ->assertStatus(403);
        });

        it('el consultor es bloqueado y no ve la tabla de usuarios', function () {
            $consultor = consultorUser();

            // La respuesta debe ser 403, no una página con datos de usuarios
            $response = $this->actingAs($consultor)->get('/users');
            $response->assertStatus(403);
        });
    });

    // ══════════════════════════════════════════════
    // ENDPOINTS DE LA API
    // ══════════════════════════════════════════════

    describe('API: Crear usuario', function () {

        it('el consultor no puede crear usuarios (403)', function () {
            $this->actingAs(consultorUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Nuevo Usuario',
                    'username' => 'nuevo_user',
                    'email'    => 'nuevo@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(403);
        });

        it('al bloquear al consultor no se crea ningún usuario adicional en la BD', function () {
            // Crear el consultor ANTES de tomar el conteo base
            $consultor  = consultorUser();
            $totalAntes = User::count();

            $this->actingAs($consultor)
                ->postJson('/api/users/store', [
                    'name'     => 'No Deberia Crearse',
                    'username' => 'nodebecrearse',
                    'email'    => 'nodebe@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ]);

            // El total no debe haber cambiado tras el intento bloqueado
            expect(User::count())->toBe($totalAntes);
        });
    });

    describe('API: Editar usuario', function () {

        it('el consultor no puede editar usuarios (403)', function () {
            $consultor = consultorUser();
            $usuario   = User::factory()->administrador()->create();

            $this->actingAs($consultor)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => 'Intento Editar',
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'role'     => $usuario->role,
                ])
                ->assertStatus(403);
        });

        it('el consultor no puede editarse a sí mismo (403)', function () {
            $consultor = consultorUser();

            $this->actingAs($consultor)
                ->postJson('/api/users/update', [
                    'id'       => $consultor->id,
                    'name'     => 'Me cambio el nombre',
                    'username' => $consultor->username,
                    'email'    => $consultor->email,
                    'role'     => $consultor->role,
                ])
                ->assertStatus(403);
        });

        it('el intento de edición del consultor no modifica los datos en la BD', function () {
            $consultor   = consultorUser();
            $usuario     = User::factory()->administrador()->create(['name' => 'Nombre Original']);

            $this->actingAs($consultor)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => 'Nombre Modificado',
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'role'     => $usuario->role,
                ]);

            // Los datos NO deben haber cambiado
            $this->assertDatabaseHas('users', [
                'id'   => $usuario->id,
                'name' => 'Nombre Original',
            ]);
        });
    });

    describe('API: Eliminar usuario', function () {

        it('el consultor no puede eliminar usuarios (403)', function () {
            $consultor = consultorUser();
            $usuario   = User::factory()->administrador()->create();

            $this->actingAs($consultor)
                ->deleteJson("/api/users/delete/{$usuario->id}")
                ->assertStatus(403);
        });

        it('el intento de eliminación del consultor no borra al usuario de la BD', function () {
            $consultor = consultorUser();
            $usuario   = User::factory()->administrador()->create();

            $this->actingAs($consultor)
                ->deleteJson("/api/users/delete/{$usuario->id}");

            // El usuario sigue existiendo en la BD
            $this->assertDatabaseHas('users', ['id' => $usuario->id]);
        });

        it('el consultor no puede eliminar su propio usuario (403)', function () {
            $consultor = consultorUser();

            $this->actingAs($consultor)
                ->deleteJson("/api/users/delete/{$consultor->id}")
                ->assertStatus(403);
        });
    });
});
