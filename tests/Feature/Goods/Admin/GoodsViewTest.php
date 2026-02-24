<?php

/**
 * Tests de la VISTA /goods para el rol ADMINISTRADOR.
 *
 * Verifica que:
 *   - La página carga correctamente (200)
 *   - El título del catálogo está presente
 *   - Los botones de crear, editar, eliminar y Excel son visibles
 *   - Los modales de crear/editar están incluidos en el HTML
 *   - Los datos de los bienes se muestran correctamente
 *   - El estado vacío se muestra cuando no hay bienes
 *   - El campo de búsqueda está presente
 */

describe('Vista /goods - Administrador', function () {

    // ══════════════════════════════════════════════
    // ACCESO A LA VISTA
    // ══════════════════════════════════════════════

    describe('Acceso a la vista', function () {

        it('el administrador puede acceder a /goods (200)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertStatus(200);
        });

        it('la página muestra el título del catálogo', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertSee('Catalogo de bienes');
        });

        it('usuario no autenticado es redirigido al login', function () {
            $this->get(route('goods.index'))
                ->assertRedirect('/login');
        });
    });

    // ══════════════════════════════════════════════
    // BOTONES DE ACCIÓN DEL ADMINISTRADOR
    // ══════════════════════════════════════════════

    describe('Botones de acción del administrador', function () {

        it('muestra el botón "Crear" en la barra superior', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertSee('Crear');
        });

        it('muestra el botón de subir Excel (excel-upload-btn)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertSee('excel-upload-btn');
        });

        it('muestra el modal de crear bien (modalCrearBien)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertSee('modalCrearBien');
        });

        it('muestra el modal de actualizar bien (modalActualizarBien)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertSee('modalActualizarBien');
        });

        it('muestra los botones de editar cuando hay bienes', function () {
            $admin = adminUser();
            crearBien(['name' => 'Bien Con Botones']);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertSee('btn-editar');
        });

        it('muestra los botones de eliminar cuando hay bienes', function () {
            $admin = adminUser();
            crearBien(['name' => 'Bien Eliminable']);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertSee('btn-eliminar');
        });

        it('el botón de editar llama a btnEditarBien con el id del bien', function () {
            $admin = adminUser();
            $asset = crearBien(['name' => 'Bien Editable']);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertSee('btnEditarBien(' . $asset->id, false);
        });

        it('el botón de eliminar llama a eliminarBien con el id del bien', function () {
            $admin = adminUser();
            $asset = crearBien(['name' => 'Bien A Borrar']);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertSee('eliminarBien(' . $asset->id . ')', false);
        });
    });

    // ══════════════════════════════════════════════
    // DATOS DE BIENES EN LA VISTA
    // ══════════════════════════════════════════════

    describe('Datos de bienes en la vista', function () {

        it('muestra el nombre de un bien creado', function () {
            $admin = adminUser();
            crearBien(['name' => 'Pizarrón Interactivo']);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertSee('Pizarrón Interactivo');
        });

        it('muestra múltiples bienes en la vista', function () {
            $admin = adminUser();
            crearBien(['name' => 'Escritorio Madera']);
            crearBien(['name' => 'Silla Plástica']);
            crearBien(['name' => 'Tablero Acrílico']);

            $response = $this->actingAs($admin)->get(route('goods.index'));

            $response->assertSee('Escritorio Madera')
                ->assertSee('Silla Plástica')
                ->assertSee('Tablero Acrílico');
        });

        it('el bien recién creado via API aparece en la vista', function () {
            $admin = adminUser();

            $this->actingAs($admin)->postJson(route('goods.store'), [
                'nombre' => 'Bien Recién Creado',
                'tipo'   => 1,
            ]);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertSee('Bien Recién Creado');
        });

        it('el campo de búsqueda está presente (searchGood)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertSee('searchGood');
        });
    });

    // ══════════════════════════════════════════════
    // ESTADO VACÍO vs ESTADO CON BIENES
    // ══════════════════════════════════════════════

    describe('Estado vacío vs catálogo con bienes', function () {

        it('muestra el estado vacío cuando no hay bienes', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertSee('No hay bienes disponibles');
        });

        it('no muestra el estado vacío cuando hay bienes', function () {
            $admin = adminUser();
            crearBien(['name' => 'Ventilador']);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertDontSee('No hay bienes disponibles');
        });

        it('la cuadrícula de bienes (bienes-grid) aparece cuando hay bienes', function () {
            $admin = adminUser();
            crearBien(['name' => 'Monitor']);

            $this->actingAs($admin)
                ->get(route('goods.index'))
                ->assertSee('bienes-grid');
        });

        it('la cuadrícula de bienes no aparece con estado vacío', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.index'))
                ->assertDontSee('bienes-grid');
        });
    });
});
