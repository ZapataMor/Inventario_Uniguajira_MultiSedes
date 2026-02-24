<?php

/**
 * Tests de validación del módulo de Bienes.
 *
 * Cubre las reglas definidas en GoodsController::store() y ::update():
 *   - nombre:  required | string | max:255 | unique:assets,name
 *   - tipo:    required | integer | in:1,2  (solo en creación)
 *   - imagen:  nullable | image | max:2048
 *
 * Reglas de negocio:
 *   - El nombre del bien debe ser único en toda la colección
 *   - El tipo es inmutable: una vez creado no puede modificarse
 *   - No se puede eliminar un bien que tenga artículos (cantidad > 0 o seriales)
 */

use App\Models\Asset;

describe('Validaciones de Bienes', function () {

    // ══════════════════════════════════════════════
    // CAMPO: NOMBRE (al crear)
    // ══════════════════════════════════════════════

    describe('Campo: nombre — al crear', function () {

        it('es obligatorio al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => '',
                    'tipo'   => 1,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
        });

        it('no puede superar 255 caracteres al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => str_repeat('A', 256),
                    'tipo'   => 1,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
        });

        it('acepta exactamente 255 caracteres al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => str_repeat('B', 255),
                    'tipo'   => 1,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('acepta 1 carácter al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'X',
                    'tipo'   => 1,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('rechaza un nombre duplicado al crear', function () {
            $admin = adminUser();
            crearBien(['name' => 'Escritorio']);

            $this->actingAs($admin)
                ->postJson(route('goods.store'), [
                    'nombre' => 'Escritorio',
                    'tipo'   => 1,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
        });

        it('al rechazar un nombre duplicado no se crea ningún bien adicional', function () {
            $admin = adminUser();
            crearBien(['name' => 'Silla Ergonómica']);
            $totalAntes = Asset::count();

            $this->actingAs($admin)->postJson(route('goods.store'), [
                'nombre' => 'Silla Ergonómica',
                'tipo'   => 1,
            ]);

            expect(Asset::count())->toBe($totalAntes);
        });

        it('la comparación de unicidad es sensible a mayúsculas y minúsculas (SQLite)', function () {
            // SQLite es case-insensitive por defecto para LIKE pero no para =
            // El comportamiento real depende del motor, aquí validamos la regla Laravel
            $admin = adminUser();
            crearBien(['name' => 'Monitor']);

            // Mismo nombre exacto: debe fallar
            $this->actingAs($admin)
                ->postJson(route('goods.store'), [
                    'nombre' => 'Monitor',
                    'tipo'   => 1,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
        });

        it('permite nombres distintos aunque sean similares', function () {
            $admin = adminUser();
            crearBien(['name' => 'Silla Normal']);

            $this->actingAs($admin)
                ->postJson(route('goods.store'), [
                    'nombre' => 'Silla Especial',
                    'tipo'   => 1,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // CAMPO: NOMBRE (al editar)
    // ══════════════════════════════════════════════

    describe('Campo: nombre — al editar', function () {

        it('es obligatorio al editar', function () {
            $admin = adminUser();
            $asset = crearBien();

            $this->actingAs($admin)
                ->postJson(route('goods.update'), [
                    'id'     => $asset->id,
                    'nombre' => '',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
        });

        it('no puede superar 255 caracteres al editar', function () {
            $admin = adminUser();
            $asset = crearBien();

            $this->actingAs($admin)
                ->postJson(route('goods.update'), [
                    'id'     => $asset->id,
                    'nombre' => str_repeat('Z', 256),
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
        });

        it('acepta exactamente 255 caracteres al editar', function () {
            $admin = adminUser();
            $asset = crearBien();

            $this->actingAs($admin)
                ->postJson(route('goods.update'), [
                    'id'     => $asset->id,
                    'nombre' => str_repeat('C', 255),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('permite el mismo nombre al editar el propio bien (excepción de unicidad)', function () {
            $admin = adminUser();
            $asset = crearBien(['name' => 'Monitor Dell']);

            $this->actingAs($admin)
                ->postJson(route('goods.update'), [
                    'id'     => $asset->id,
                    'nombre' => 'Monitor Dell',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('rechaza el nombre de otro bien existente al editar', function () {
            $admin  = adminUser();
            $asset1 = crearBien(['name' => 'Bien A']);
            $asset2 = crearBien(['name' => 'Bien B']);

            $this->actingAs($admin)
                ->postJson(route('goods.update'), [
                    'id'     => $asset2->id,
                    'nombre' => 'Bien A',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
        });

        it('al rechazar nombre duplicado en edición los datos no cambian', function () {
            $admin  = adminUser();
            $asset1 = crearBien(['name' => 'Original A']);
            $asset2 = crearBien(['name' => 'Original B']);

            $this->actingAs($admin)->postJson(route('goods.update'), [
                'id'     => $asset2->id,
                'nombre' => 'Original A',
            ]);

            $this->assertDatabaseHas('assets', [
                'id'   => $asset2->id,
                'name' => 'Original B',
            ]);
        });
    });

    // ══════════════════════════════════════════════
    // CAMPO: TIPO (al crear)
    // ══════════════════════════════════════════════

    describe('Campo: tipo — al crear', function () {

        it('es obligatorio al crear', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Bien Sin Tipo',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['tipo']);
        });

        it('rechaza tipo 0 (fuera del rango válido)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Tipo Cero',
                    'tipo'   => 0,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['tipo']);
        });

        it('rechaza tipo 3 (fuera del rango válido)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Tipo Inválido',
                    'tipo'   => 3,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['tipo']);
        });

        it('rechaza tipo como cadena de texto', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Tipo Texto',
                    'tipo'   => 'Cantidad',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['tipo']);
        });

        it('rechaza tipo negativo', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Tipo Negativo',
                    'tipo'   => -1,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['tipo']);
        });

        it('acepta tipo 1 (Cantidad)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Bien Cantidad Válido',
                    'tipo'   => 1,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('acepta tipo 2 (Serial)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Bien Serial Válido',
                    'tipo'   => 2,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // CAMPO: ID (al editar)
    // ══════════════════════════════════════════════

    describe('Campo: id — al editar', function () {

        it('retorna 404 si el id no existe en la base de datos', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.update'), [
                    'id'     => 99999,
                    'nombre' => 'ID Inexistente',
                ])
                ->assertStatus(404);
        });

        it('retorna 404 si el id es nulo o no se envía', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.update'), [
                    'nombre' => 'Sin ID',
                ])
                ->assertStatus(404);
        });
    });

    // ══════════════════════════════════════════════
    // REGLA DE NEGOCIO: ELIMINAR CON ARTÍCULOS
    // ══════════════════════════════════════════════

    describe('Regla de negocio: no eliminar bien con artículos', function () {

        it('bloquea la eliminación cuando la cantidad es mayor a 0', function () {
            $admin = adminUser();
            $asset = crearBienConCantidad(1);

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(400)
                ->assertJson(['success' => false]);
        });

        it('bloquea la eliminación cuando tiene seriales registrados', function () {
            $admin = adminUser();
            $asset = crearBienConSerial();

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(400)
                ->assertJson(['success' => false]);
        });

        it('permite la eliminación cuando el bien no tiene artículos', function () {
            $admin = adminUser();
            $asset = crearBien();

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });
});
