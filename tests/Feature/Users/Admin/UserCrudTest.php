<?php

/**
 * Tests de CRUD de usuarios para el rol ADMINISTRADOR.
 *
 * Cubre las tres operaciones:
 *   - Crear  → POST   /api/users/store
 *   - Editar → POST   /api/users/update
 *   - Borrar → DELETE /api/users/delete/{id}
 *
 * Reglas de negocio críticas:
 *   - El usuario con id=1 no puede eliminarse nunca
 *   - Un usuario no puede eliminarse a sí mismo
 *   - Un usuario no puede cambiar su propio rol
 */

use App\Models\User;

describe('CRUD de Usuarios - Administrador', function () {

    // ══════════════════════════════════════════════
    // CREAR USUARIO
    // ══════════════════════════════════════════════

    describe('Crear usuario', function () {

        it('puede crear un usuario administrador con todos los campos', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson('/api/users/store', [
                    'name'     => 'Nuevo Administrador',
                    'username' => 'nuevoadmin',
                    'email'    => 'nuevoadmin@uniguajira.edu.co',
                    'password' => 'clave123',
                    'role'     => 'administrador',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('users', [
                'name'     => 'Nuevo Administrador',
                'username' => 'nuevoadmin',
                'email'    => 'nuevoadmin@uniguajira.edu.co',
                'role'     => 'administrador',
            ]);
        });

        it('puede crear un usuario con rol consultor', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson('/api/users/store', [
                    'name'     => 'Consultor Nuevo',
                    'username' => 'consultor99',
                    'email'    => 'consultor99@uniguajira.edu.co',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('users', [
                'email' => 'consultor99@uniguajira.edu.co',
                'role'  => 'consultor',
            ]);
        });

        it('la contraseña se guarda hasheada (no en texto plano)', function () {
            $admin = adminUser();

            $this->actingAs($admin)->postJson('/api/users/store', [
                'name'     => 'Usuario Hash',
                'username' => 'usuariohash',
                'email'    => 'hash@test.com',
                'password' => 'mipassword',
                'role'     => 'consultor',
            ]);

            $usuario = User::where('email', 'hash@test.com')->first();

            // La contraseña NO debe estar en texto plano
            expect($usuario->password)->not->toBe('mipassword');
            // Debe ser un hash de bcrypt (empieza con $2y$)
            expect($usuario->password)->toStartWith('$2y$');
        });

        it('la respuesta contiene el mensaje de éxito en español', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson('/api/users/store', [
                    'name'     => 'Test Mensaje',
                    'username' => 'testmensaje',
                    'email'    => 'testmensaje@test.com',
                    'password' => 'clave123',
                    'role'     => 'administrador',
                ])
                ->assertJson(['message' => 'Usuario creado correctamente.']);
        });

        it('el usuario creado aparece en la tabla de la vista /users', function () {
            $admin = adminUser();

            $this->actingAs($admin)->postJson('/api/users/store', [
                'name'     => 'Andrés Visible',
                'username' => 'andresvisible',
                'email'    => 'andres@visible.com',
                'password' => 'clave123',
                'role'     => 'administrador',
            ]);

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('Andrés Visible')
                ->assertSee('andresvisible')
                ->assertSee('andres@visible.com');
        });

        it('no se puede crear un usuario si el email ya existe', function () {
            $admin   = adminUser();
            $existente = User::factory()->administrador()->create([
                'email' => 'repetido@test.com',
            ]);

            $this->actingAs($admin)
                ->postJson('/api/users/store', [
                    'name'     => 'Otro Nombre',
                    'username' => 'otrousername',
                    'email'    => 'repetido@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJson(['success' => false]);
        });

        it('no se puede crear un usuario si el username ya existe', function () {
            $admin = adminUser();
            User::factory()->administrador()->create(['username' => 'usernamerepetido']);

            $this->actingAs($admin)
                ->postJson('/api/users/store', [
                    'name'     => 'Nombre Diferente',
                    'username' => 'usernamerepetido',
                    'email'    => 'diferente@test.com',
                    'password' => 'clave123',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422)
                ->assertJson(['success' => false]);
        });

        it('crear un usuario incrementa el total de usuarios en la base de datos', function () {
            $admin = adminUser();
            $totalAntes = User::count();

            $this->actingAs($admin)->postJson('/api/users/store', [
                'name'     => 'Conteo Usuario',
                'username' => 'conteousuario',
                'email'    => 'conteo@test.com',
                'password' => 'clave123',
                'role'     => 'consultor',
            ]);

            expect(User::count())->toBe($totalAntes + 1);
        });
    });

    // ══════════════════════════════════════════════
    // EDITAR USUARIO
    // ══════════════════════════════════════════════

    describe('Editar usuario', function () {

        it('puede actualizar el nombre de un usuario', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create(['name' => 'Nombre Viejo']);

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => 'Nombre Nuevo',
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('users', [
                'id'   => $usuario->id,
                'name' => 'Nombre Nuevo',
            ]);
        });

        it('puede actualizar el email de un usuario', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => $usuario->username,
                    'email'    => 'nuevo@correo.com',
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('users', [
                'id'    => $usuario->id,
                'email' => 'nuevo@correo.com',
            ]);
        });

        it('puede actualizar el username de un usuario', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => 'nuevousername',
                    'email'    => $usuario->email,
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('users', [
                'id'       => $usuario->id,
                'username' => 'nuevousername',
            ]);
        });

        it('puede cambiar el rol de un usuario (de consultor a administrador)', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'role'     => 'administrador',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('users', [
                'id'   => $usuario->id,
                'role' => 'administrador',
            ]);
        });

        it('puede actualizar la contraseña de un usuario', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'password' => 'nuevaclave456',
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            // La nueva contraseña debe estar hasheada
            $actualizado = User::find($usuario->id);
            expect($actualizado->password)->not->toBe('nuevaclave456');
            expect($actualizado->password)->toStartWith('$2y$');
        });

        it('no actualiza la contraseña si el campo viene vacío', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();
            $passwordOriginal = $usuario->password;

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => $usuario->username,
                    'email'    => $usuario->email,
                    'password' => '',   // vacío → no cambia
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            // La contraseña no debe haber cambiado
            expect(User::find($usuario->id)->password)->toBe($passwordOriginal);
        });

        it('un administrador no puede cambiar su propio rol', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $admin->id,
                    'name'     => $admin->name,
                    'username' => $admin->username,
                    'email'    => $admin->email,
                    'role'     => 'consultor',   // intenta cambiar su propio rol
                ])
                ->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'No puedes cambiar tu propio rol.',
                ]);

            // El rol no debe haber cambiado
            $this->assertDatabaseHas('users', [
                'id'   => $admin->id,
                'role' => 'administrador',
            ]);
        });

        it('puede actualizar sus propios datos excepto el rol', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $admin->id,
                    'name'     => 'Mi Nombre Actualizado',
                    'username' => $admin->username,
                    'email'    => $admin->email,
                    'role'     => 'administrador',  // mismo rol → sin cambio
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('users', [
                'id'   => $admin->id,
                'name' => 'Mi Nombre Actualizado',
            ]);
        });

        it('permite usar el mismo email en la edición del propio usuario (unique ignore)', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create(['email' => 'mismo@correo.com']);

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => 'Nombre Actualizado',
                    'username' => $usuario->username,
                    'email'    => 'mismo@correo.com',  // mismo email → debe pasar
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('permite usar el mismo username en la edición del propio usuario (unique ignore)', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create(['username' => 'mismo_user']);

            $this->actingAs($admin)
                ->postJson('/api/users/update', [
                    'id'       => $usuario->id,
                    'name'     => $usuario->name,
                    'username' => 'mismo_user',  // mismo username → debe pasar
                    'email'    => $usuario->email,
                    'role'     => $usuario->role,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('retorna error 422 al intentar editar un usuario inexistente', function () {
            $this->actingAs(adminUser())
                ->postJson('/api/users/update', [
                    'id'       => 99999,
                    'name'     => 'No existe',
                    'username' => 'noexiste',
                    'email'    => 'noexiste@test.com',
                    'role'     => 'consultor',
                ])
                ->assertStatus(422);
        });
    });

    // ══════════════════════════════════════════════
    // ELIMINAR USUARIO
    // ══════════════════════════════════════════════

    describe('Eliminar usuario', function () {

        it('puede eliminar un usuario consultor', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->deleteJson("/api/users/delete/{$usuario->id}")
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
        });

        it('puede eliminar un usuario administrador (que no sea id=1 ni sí mismo)', function () {
            // Asegurarse de que el admin tenga un id != 1
            $admin        = adminUser();
            $otroAdmin    = User::factory()->administrador()->create();

            $this->actingAs($admin)
                ->deleteJson("/api/users/delete/{$otroAdmin->id}")
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseMissing('users', ['id' => $otroAdmin->id]);
        });

        it('no puede eliminar su propio usuario', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->deleteJson("/api/users/delete/{$admin->id}")
                ->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario.',
                ]);

            // El usuario sigue en la base de datos
            $this->assertDatabaseHas('users', ['id' => $admin->id]);
        });

        it('el usuario con id=1 no puede ser eliminado (protección principal)', function () {
            // Crear el admin con id forzado a 1 (se logra siendo el primer usuario creado)
            User::query()->delete(); // limpiar para que el siguiente sea id=1
            $adminPrincipal = User::factory()->administrador()->create(); // id=1
            $otroAdmin      = User::factory()->administrador()->create(); // id=2+

            $this->actingAs($otroAdmin)
                ->deleteJson("/api/users/delete/{$adminPrincipal->id}")
                ->assertStatus(422)
                ->assertJson(['success' => false]);

            $this->assertDatabaseHas('users', ['id' => $adminPrincipal->id]);
        });

        it('retorna 404 al intentar eliminar un usuario inexistente', function () {
            $this->actingAs(adminUser())
                ->deleteJson('/api/users/delete/99999')
                ->assertStatus(404);
        });

        it('no elimina otros usuarios al eliminar uno específico', function () {
            $admin    = adminUser();
            $usuario1 = User::factory()->consultor()->create(['name' => 'Usuario que se queda']);
            $usuario2 = User::factory()->consultor()->create(['name' => 'Usuario que se borra']);

            $this->actingAs($admin)
                ->deleteJson("/api/users/delete/{$usuario2->id}")
                ->assertStatus(200);

            $this->assertDatabaseHas('users', ['id' => $usuario1->id]);
            $this->assertDatabaseMissing('users', ['id' => $usuario2->id]);
        });

        it('la eliminación devuelve el mensaje de éxito en español', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->deleteJson("/api/users/delete/{$usuario->id}")
                ->assertJson(['message' => 'Usuario eliminado correctamente.']);
        });

        it('eliminar un usuario reduce el total en la base de datos', function () {
            $admin   = adminUser();
            $usuario = User::factory()->consultor()->create();
            $totalAntes = User::count();

            $this->actingAs($admin)
                ->deleteJson("/api/users/delete/{$usuario->id}");

            expect(User::count())->toBe($totalAntes - 1);
        });
    });
});
