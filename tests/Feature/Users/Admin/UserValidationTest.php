<?php

/**
 * Tests de VALIDACIÓN del módulo de Usuarios.
 *
 * Cubre las reglas definidas en UserController::store() y ::update():
 *   - name:     required | string | max:255
 *   - username: required | string | max:255 | unique:users
 *   - email:    required | email  | max:255 | unique:users
 *   - password: required (store) | nullable | min:6 (update)
 *   - role:     required | in:administrador,consultor
 */

use App\Models\User;

describe('Validaciones de Usuarios', function () {

    // ══════════════════════════════════════════════
    // CAMPO: NOMBRE (name)
    // ══════════════════════════════════════════════

    describe('Campo: nombre (name)', function () {

        it('es obligatorio al crear un usuario', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => '',
                    'username' => 'test_user',
                    'email'    => 'test@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('es obligatorio al editar un usuario', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => '',
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'role'     => $usuario->role,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('no puede superar 255 caracteres al crear', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => str_repeat('A', 256),
                    'username' => 'test_user',
                    'email'    => 'test@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('acepta exactamente 255 caracteres en el nombre', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => str_repeat('B', 255),
                    'username' => 'nombre_255',
                    'email'    => 'nombre255@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // CAMPO: USERNAME
    // ══════════════════════════════════════════════

    describe('Campo: username', function () {

        it('es obligatorio al crear un usuario', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Sin Username',
                    'username' => '',
                    'email'    => 'sinuser@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        });

        it('es obligatorio al editar un usuario', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => '',
                    'email'    => $usuario->email,
                    'role'     => $usuario->role,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        });

        it('debe ser único al crear (no puede repetirse con otro usuario)', function () {
            $admin = adminUser();
            User::factory()->consultor()->create(['username' => 'repetido_user']);

            $this->actingAs($admin)
                ->postJson('/api/users/store', [
                    'name'     => 'Nombre Cualquiera',
                    'username' => 'repetido_user',
                    'email'    => 'otro@correo.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        });

        it('debe ser único al editar (no puede coincidir con otro usuario)', function () {
            $admin    = adminUser();
            $usuario1 = User::factory()->consultor()->create(['username' => 'user_ocupado']);
            $usuario2 = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario2->id,
                    'name'     => $usuario2->name,
                    'username' => 'user_ocupado',   // ya lo tiene usuario1
                    'email'    => $usuario2->email,
                    'role'     => $usuario2->role,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        });

        it('no puede superar 255 caracteres', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Nombre',
                    'username' => str_repeat('u', 256),
                    'email'    => 'largousername@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        });
    });

    // ══════════════════════════════════════════════
    // CAMPO: EMAIL
    // ══════════════════════════════════════════════

    describe('Campo: email', function () {

        it('es obligatorio al crear un usuario', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Sin Email',
                    'username' => 'sin_email',
                    'email'    => '',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('debe tener formato de email válido al crear', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Email Inválido',
                    'username' => 'emailinvalido',
                    'email'    => 'esto-no-es-un-email',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('debe tener formato de email válido al editar', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => $usuario->username,
                    'email'    => 'formato-invalido',
                    'role'     => $usuario->role,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('debe ser único al crear (no puede repetirse con otro usuario)', function () {
            $admin = adminUser();
            User::factory()->consultor()->create(['email' => 'repetido@correo.com']);

            $this->actingAs($admin)
                ->postJson('/api/users/store', [
                    'name'     => 'Nombre',
                    'username' => 'otrousername2',
                    'email'    => 'repetido@correo.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('debe ser único al editar (no puede coincidir con otro usuario)', function () {
            $admin    = adminUser();
            $usuario1 = User::factory()->consultor()->create(['email' => 'email_ocupado@test.com']);
            $usuario2 = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario2->id,
                    'name'     => $usuario2->name,
                    'username' => $usuario2->username,
                    'email'    => 'email_ocupado@test.com',  // ya lo tiene usuario1
                    'role'     => $usuario2->role,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('acepta un email con formato correcto al crear', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Email Válido',
                    'username' => 'emailvalido',
                    'email'    => 'valido@uniguajira.edu.co',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // CAMPO: CONTRASEÑA (password)
    // ══════════════════════════════════════════════

    describe('Campo: contraseña (password)', function () {

        it('es obligatoria al crear un usuario', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Sin Password',
                    'username' => 'sin_password',
                    'email'    => 'sinpassword@test.com',
                    'password' => '',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('debe tener al menos 6 caracteres al crear', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Clave Corta',
                    'username' => 'clave_corta',
                    'email'    => 'clavecorta@test.com',
                    'password' => '12345',  // 5 caracteres → inválido
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('acepta exactamente 6 caracteres al crear', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Clave Exacta',
                    'username' => 'clave_exacta',
                    'email'    => 'claveexacta@test.com',
                    'password' => '123456',  // 6 caracteres → válido
                    'role'     => 'consultor',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('debe tener al menos 6 caracteres al editar (si se proporciona)', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'password' => '123',    // 3 caracteres → inválido
                    'role'     => $usuario->role,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('es opcional al editar (puede omitirse sin error)', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    // sin campo 'password'
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // CAMPO: ROL (role)
    // ══════════════════════════════════════════════

    describe('Campo: rol (role)', function () {

        it('es obligatorio al crear un usuario', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Sin Rol',
                    'username' => 'sin_rol',
                    'email'    => 'sinrol@test.com',
                    'password' => 'clave123',
                    'role'     => '',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['role']);
        });

        it('solo acepta "administrador" como rol válido', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Rol Válido Admin',
                    'username' => 'rolvalidoadmin',
                    'email'    => 'rolvalido@test.com',
                    'password' => 'clave123',
                    'role'     => 'administrador',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('solo acepta "consultor" como rol válido', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Rol Válido Consultor',
                    'username' => 'rolvalidoconsultor',
                    'email'    => 'rolvalidoc@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('rechaza cualquier rol que no sea "administrador" o "consultor"', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Rol Inválido',
                    'username' => 'rolinvalido',
                    'email'    => 'rolinvalido@test.com',
                    'password' => 'clave123',
                    'role'     => 'superadmin',   // rol inválido
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['role']);
        });

        it('rechaza el rol "editor" como inválido', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [
                    'name'     => 'Rol Editor',
                    'username' => 'roleditor',
                    'email'    => 'editor@test.com',
                    'password' => 'clave123',
                    'role'     => 'editor',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['role']);
        });
    });

    // ══════════════════════════════════════════════
    // RESPUESTA DE ERROR
    // ══════════════════════════════════════════════

    describe('Estructura de la respuesta de error', function () {

        it('la respuesta de error tiene success=false al crear con datos inválidos', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/store', [])
                ->assertStatus(422)
                ->assertJson(['success' => false]);
        });

        it('la respuesta de error incluye el campo "errors" con los campos inválidos', function () {
            $response = $this->actingAs(adminUser())
                ->postJson('/api/users/store', []);

            $response->assertStatus(422);
            $data = $response->json();

            expect($data)->toHaveKey('errors');
            expect($data['errors'])->toHaveKey('name');
            expect($data['errors'])->toHaveKey('email');
            expect($data['errors'])->toHaveKey('password');
            expect($data['errors'])->toHaveKey('role');
        });

        it('la respuesta incluye el primer mensaje de error en el campo "message"', function () {
            $response = $this->actingAs(adminUser())
                ->postJson('/api/users/store', []);

            $response->assertStatus(422);
            $data = $response->json();

            expect($data)->toHaveKey('message');
            expect($data['message'])->toBeString();
            expect(strlen($data['message']))->toBeGreaterThan(0);
        });
    });
});
