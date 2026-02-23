<?php

/**
 * Tests de la VISTA /users para el rol ADMINISTRADOR.
 *
 * Verifica que:
 *   - La página carga correctamente (200)
 *   - La estructura de la tabla está presente (cabeceras, columnas)
 *   - Los datos de cada usuario se muestran en la tabla
 *   - Los botones de editar y eliminar están presentes
 *   - El modal de crear usuario está incluido en el HTML
 *   - El estado vacío se muestra cuando no hay usuarios
 *   - El título y elementos de navegación son correctos
 */

use App\Models\User;

describe('Vista /users - Administrador', function () {

    // ══════════════════════════════════════════════
    // ACCESO Y CARGA DE LA PÁGINA
    // ══════════════════════════════════════════════

    describe('Acceso a la vista', function () {

        it('el administrador puede acceder a /users (200)', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertStatus(200);
        });

        it('la página muestra el título "Usuarios"', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertSee('Usuarios');
        });

        it('usuario no autenticado es redirigido al login', function () {
            $this->get('/users')
                ->assertRedirect('/login');
        });
    });

    // ══════════════════════════════════════════════
    // ESTRUCTURA DE LA TABLA
    // ══════════════════════════════════════════════

    describe('Estructura de la tabla', function () {

        it('muestra las cabeceras correctas de la tabla', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertSee('ID')
                ->assertSee('Nombre')
                ->assertSee('Usuario')
                ->assertSee('Email')
                ->assertSee('Rol')
                ->assertSee('Registrado')
                ->assertSee('Acciones');
        });

        it('la tabla tiene el ID correcto para la búsqueda (tableBody)', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertSee('tableBody');
        });

        it('el campo de búsqueda está presente en la vista', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertSee('searchInput');
        });
    });

    // ══════════════════════════════════════════════
    // DATOS DE USUARIOS EN LA TABLA
    // ══════════════════════════════════════════════

    describe('Datos de usuarios en la tabla', function () {

        it('muestra el nombre de un usuario en la tabla', function () {
            $admin   = adminUser();
            $usuario = User::factory()->administrador()->create(['name' => 'Carlos Pérez']);

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('Carlos Pérez');
        });

        it('muestra el username de un usuario en la tabla', function () {
            $admin   = adminUser();
            $usuario = User::factory()->administrador()->create(['username' => 'carlospz99']);

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('carlospz99');
        });

        it('muestra el email de un usuario en la tabla', function () {
            $admin   = adminUser();
            $usuario = User::factory()->administrador()->create(['email' => 'carlos@uniguajira.edu.co']);

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('carlos@uniguajira.edu.co');
        });

        it('muestra el badge de rol "Administrador" para usuarios admin', function () {
            $admin   = adminUser();
            User::factory()->administrador()->create();

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('role-admin');
        });

        it('muestra el badge de rol "Consultor" para usuarios consultor', function () {
            $admin = adminUser();
            User::factory()->consultor()->create();

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('role-consultor');
        });

        it('muestra múltiples usuarios en la tabla', function () {
            $admin = adminUser();
            User::factory()->administrador()->create(['name' => 'Usuario Uno']);
            User::factory()->consultor()->create(['name'    => 'Usuario Dos']);
            User::factory()->consultor()->create(['name'    => 'Usuario Tres']);

            $response = $this->actingAs($admin)->get('/users');

            $response->assertSee('Usuario Uno')
                     ->assertSee('Usuario Dos')
                     ->assertSee('Usuario Tres');
        });

        it('el usuario recién creado aparece en la tabla con todos sus datos', function () {
            $admin = adminUser();

            // Crear via API para simular el flujo real
            $this->actingAs($admin)->postJson('/api/users/store', [
                'name'     => 'Laura González',
                'username' => 'laurag',
                'email'    => 'laura@uniguajira.edu.co',
                'password' => 'secreto123',
                'role'     => 'consultor',
            ]);

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('Laura González')
                ->assertSee('laurag')
                ->assertSee('laura@uniguajira.edu.co')
                ->assertSee('role-consultor');
        });
    });

    // ══════════════════════════════════════════════
    // BOTONES DE ACCIÓN
    // ══════════════════════════════════════════════

    describe('Botones de acción (editar / eliminar)', function () {

        it('muestra el botón de editar cuando hay usuarios', function () {
            $admin = adminUser();
            User::factory()->administrador()->create();

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('action-btn-edit');
        });

        it('muestra el botón de eliminar cuando hay usuarios', function () {
            $admin = adminUser();
            User::factory()->administrador()->create();

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('action-btn-delete');
        });

        it('el botón de editar lleva el data-id del usuario correcto', function () {
            $admin   = adminUser();
            $usuario = User::factory()->administrador()->create();

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('data-id="' . $usuario->id . '"', false);
        });

        it('el botón de eliminar llama a mostrarConfirmacion con el id del usuario', function () {
            $admin   = adminUser();
            $usuario = User::factory()->administrador()->create();

            $this->actingAs($admin)
                ->get('/users')
                ->assertSee('mostrarConfirmacion(' . $usuario->id . ')', false);
        });
    });

    // ══════════════════════════════════════════════
    // MODALES INCLUIDOS EN LA VISTA
    // ══════════════════════════════════════════════

    describe('Modales incluidos en la vista', function () {

        it('el modal de crear usuario está incluido (modalCrearUsuario)', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertSee('modalCrearUsuario');
        });

        it('el modal de editar usuario está incluido (modalEditarUsuario)', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertSee('modalEditarUsuario');
        });

        it('el modal de confirmar eliminación está incluido (modalConfirmarEliminar)', function () {
            $this->actingAs(adminUser())
                ->get('/users')
                ->assertSee('modalConfirmarEliminar');
        });
    });

    // ══════════════════════════════════════════════
    // ESTADO VACÍO VS. TABLA CON DATOS
    // Nota: el estado vacío real (0 usuarios) es imposible estando
    // autenticado, pues siempre existe al menos el usuario logueado.
    // Se prueba el comportamiento con 1 y múltiples usuarios.
    // ══════════════════════════════════════════════

    describe('Estado vacío vs tabla con datos', function () {

        it('con solo el usuario logueado no muestra el mensaje de vacío', function () {
            // El admin es el único usuario → la tabla debe mostrarlo
            $admin = adminUser();

            $this->actingAs($admin)
                ->get('/users')
                ->assertDontSee('No hay usuarios registrados')
                ->assertSee($admin->name);
        });

        it('con varios usuarios no muestra el mensaje de vacío', function () {
            $admin = adminUser();
            User::factory()->consultor()->create(['name' => 'Extra Consultor']);

            $this->actingAs($admin)
                ->get('/users')
                ->assertDontSee('No hay usuarios registrados')
                ->assertSee('Extra Consultor');
        });

        it('la tabla muestra todos los usuarios existentes en la base de datos', function () {
            $admin = adminUser();
            $u1    = User::factory()->administrador()->create(['name' => 'Admin Dos']);
            $u2    = User::factory()->consultor()->create(['name'    => 'Consultor Uno']);

            $response = $this->actingAs($admin)->get('/users');

            $response->assertSee($admin->name)
                     ->assertSee('Admin Dos')
                     ->assertSee('Consultor Uno');
        });
    });
});
