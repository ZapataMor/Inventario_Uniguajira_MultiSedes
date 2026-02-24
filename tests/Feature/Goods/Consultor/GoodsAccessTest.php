<?php

/**
 * Tests de acceso al módulo de Bienes para el rol CONSULTOR
 * y para usuarios no autenticados.
 *
 * Reglas verificadas:
 *   - GET  /goods                → 200 para consultor, redirect para no autenticado
 *   - POST /api/goods/create     → 403 para consultor, 401 para no autenticado
 *   - POST /api/goods/update     → 403 para consultor, 401 para no autenticado
 *   - DELETE /api/goods/delete/{id} → 403 para consultor, 401 para no autenticado
 *
 * El consultor puede VER los bienes pero NO puede crear, editar ni eliminar.
 * Los botones de acción no deben aparecer en la vista del consultor.
 */

use App\Models\Asset;

// ══════════════════════════════════════════════
// USUARIOS NO AUTENTICADOS
// ══════════════════════════════════════════════

describe('Acceso a la API de Bienes - No autenticado', function () {

    it('no puede ver la lista de bienes (redirige al login)', function () {
        $this->get(route('goods.index'))
            ->assertRedirect('/login');
    });

    it('no puede crear un bien (401)', function () {
        $this->postJson(route('goods.store'), [
            'nombre' => 'Intento sin auth',
            'tipo'   => 1,
        ])->assertStatus(401);
    });

    it('no puede editar un bien (401)', function () {
        $asset = crearBien();

        $this->postJson(route('goods.update'), [
            'id'     => $asset->id,
            'nombre' => 'Intento sin auth',
        ])->assertStatus(401);
    });

    it('no puede eliminar un bien (401)', function () {
        $asset = crearBien();

        $this->deleteJson(route('goods.destroy', $asset->id))
            ->assertStatus(401);
    });
});

// ══════════════════════════════════════════════
// CONSULTOR — ACCESO A LA VISTA
// ══════════════════════════════════════════════

describe('Vista /goods - Consultor', function () {

    describe('Acceso a la vista', function () {

        it('el consultor puede acceder a /goods (200)', function () {
            $this->actingAs(consultorUser())
                ->get(route('goods.index'))
                ->assertStatus(200);
        });

        it('el consultor ve el título del catálogo de bienes', function () {
            $this->actingAs(consultorUser())
                ->get(route('goods.index'))
                ->assertSee('Catalogo de bienes');
        });
    });

    // ══════════════════════════════════════════════
    // BOTONES DE ACCIÓN — NO VISIBLES PARA CONSULTOR
    // ══════════════════════════════════════════════

    describe('Botones de acción (NO visibles para consultor)', function () {

        it('el consultor NO ve el botón "Crear"', function () {
            $this->actingAs(consultorUser())
                ->get(route('goods.index'))
                ->assertDontSee('create-btn');
        });

        it('el consultor NO ve el botón de subir Excel (excel-upload-btn)', function () {
            $this->actingAs(consultorUser())
                ->get(route('goods.index'))
                ->assertDontSee('excel-upload-btn');
        });

        it('el consultor NO ve el modal de crear bien (modalCrearBien)', function () {
            $this->actingAs(consultorUser())
                ->get(route('goods.index'))
                ->assertDontSee('modalCrearBien');
        });

        it('el consultor NO ve el modal de actualizar bien (modalActualizarBien)', function () {
            $this->actingAs(consultorUser())
                ->get(route('goods.index'))
                ->assertDontSee('modalActualizarBien');
        });

        it('el consultor NO ve los botones de editar cuando hay bienes', function () {
            $consultor = consultorUser();
            crearBien(['name' => 'Bien Visible']);

            $this->actingAs($consultor)
                ->get(route('goods.index'))
                ->assertDontSee('btn-editar');
        });

        it('el consultor NO ve los botones de eliminar cuando hay bienes', function () {
            $consultor = consultorUser();
            crearBien(['name' => 'Bien Visible']);

            $this->actingAs($consultor)
                ->get(route('goods.index'))
                ->assertDontSee('btn-eliminar');
        });

        it('el consultor NO ve la llamada a btnEditarBien en el HTML', function () {
            $consultor = consultorUser();
            crearBien(['name' => 'Bien Cualquiera']);

            $this->actingAs($consultor)
                ->get(route('goods.index'))
                ->assertDontSee('btnEditarBien');
        });

        it('el consultor NO ve la llamada a eliminarBien en el HTML', function () {
            $consultor = consultorUser();
            crearBien(['name' => 'Bien Cualquiera']);

            $this->actingAs($consultor)
                ->get(route('goods.index'))
                ->assertDontSee('eliminarBien');
        });
    });

    // ══════════════════════════════════════════════
    // DATOS DE BIENES — SÍ VISIBLES PARA CONSULTOR
    // ══════════════════════════════════════════════

    describe('Datos de bienes (SÍ visibles para consultor)', function () {

        it('el consultor puede ver el nombre de los bienes existentes', function () {
            $consultor = consultorUser();
            crearBien(['name' => 'Monitor Visible']);

            $this->actingAs($consultor)
                ->get(route('goods.index'))
                ->assertSee('Monitor Visible');
        });

        it('el consultor ve el estado vacío cuando no hay bienes', function () {
            $this->actingAs(consultorUser())
                ->get(route('goods.index'))
                ->assertSee('No hay bienes disponibles');
        });

        it('el consultor puede ver múltiples bienes', function () {
            $consultor = consultorUser();
            crearBien(['name' => 'Bien Uno']);
            crearBien(['name' => 'Bien Dos']);

            $response = $this->actingAs($consultor)->get(route('goods.index'));

            $response->assertSee('Bien Uno')
                ->assertSee('Bien Dos');
        });

        it('el consultor ve los bienes creados por el administrador', function () {
            $admin     = adminUser();
            $consultor = consultorUser();

            $this->actingAs($admin)->postJson(route('goods.store'), [
                'nombre' => 'Bien Del Admin',
                'tipo'   => 1,
            ]);

            $this->actingAs($consultor)
                ->get(route('goods.index'))
                ->assertSee('Bien Del Admin');
        });
    });

    // ══════════════════════════════════════════════
    // CONSULTOR — API: NO PUEDE MODIFICAR BIENES
    // ══════════════════════════════════════════════

    describe('API: el consultor no puede modificar bienes', function () {

        it('el consultor no puede crear bienes (403)', function () {
            $this->actingAs(consultorUser())
                ->postJson(route('goods.store'), [
                    'nombre' => 'Intento Crear',
                    'tipo'   => 1,
                ])
                ->assertStatus(403);
        });

        it('el intento de creación del consultor no inserta nada en la BD', function () {
            $consultor  = consultorUser();
            $totalAntes = Asset::count();

            $this->actingAs($consultor)->postJson(route('goods.store'), [
                'nombre' => 'No Debe Crearse',
                'tipo'   => 1,
            ]);

            expect(Asset::count())->toBe($totalAntes);
        });

        it('el consultor no puede editar bienes (403)', function () {
            $consultor = consultorUser();
            $asset     = crearBien(['name' => 'Bien Original']);

            $this->actingAs($consultor)
                ->postJson(route('goods.update'), [
                    'id'     => $asset->id,
                    'nombre' => 'Bien Modificado',
                ])
                ->assertStatus(403);
        });

        it('el intento de edición del consultor no modifica los datos en la BD', function () {
            $consultor = consultorUser();
            $asset     = crearBien(['name' => 'Nombre Que No Cambia']);

            $this->actingAs($consultor)->postJson(route('goods.update'), [
                'id'     => $asset->id,
                'nombre' => 'Nombre Cambiado',
            ]);

            $this->assertDatabaseHas('assets', [
                'id'   => $asset->id,
                'name' => 'Nombre Que No Cambia',
            ]);
        });

        it('el consultor no puede eliminar bienes (403)', function () {
            $consultor = consultorUser();
            $asset     = crearBien();

            $this->actingAs($consultor)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(403);
        });

        it('el intento de eliminación del consultor no borra el bien de la BD', function () {
            $consultor = consultorUser();
            $asset     = crearBien();

            $this->actingAs($consultor)
                ->deleteJson(route('goods.destroy', $asset->id));

            $this->assertDatabaseHas('assets', ['id' => $asset->id]);
        });

        it('el consultor no puede hacer carga masiva de bienes (403)', function () {
            $this->actingAs(consultorUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Masivo', 'tipo' => '1'],
                    ],
                ])
                ->assertStatus(403);
        });
    });
});
