<?php

/**
 * Tests de CRUD de Bienes para el rol ADMINISTRADOR.
 *
 * Cubre las cuatro operaciones:
 *   - Crear  → POST /api/goods/create
 *   - Editar → POST /api/goods/update
 *   - Borrar → DELETE /api/goods/delete/{id}
 *
 * Regla de negocio clave:
 *   - Un bien con artículos (cantidad > 0 o seriales) NO puede eliminarse.
 *   - Un bien sin artículos SÍ puede eliminarse.
 *   - El tipo (Cantidad / Serial) no puede modificarse tras la creación.
 */

use App\Models\Asset;
use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\Group;
use App\Models\Inventory;

describe('CRUD de Bienes - Administrador', function () {

    // ══════════════════════════════════════════════
    // CREAR BIEN
    // ══════════════════════════════════════════════

    describe('Crear bien', function () {

        it('puede crear un bien de tipo Cantidad', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('goods.store'), [
                    'nombre' => 'Escritorio de prueba',
                    'tipo'   => 1,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('assets', [
                'name' => 'Escritorio de prueba',
                'type' => 'Cantidad',
            ]);
        });

        it('puede crear un bien de tipo Serial', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('goods.store'), [
                    'nombre' => 'Computador HP',
                    'tipo'   => 2,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('assets', [
                'name' => 'Computador HP',
                'type' => 'Serial',
            ]);
        });

        it('el tipo se almacena como texto "Cantidad" al enviar tipo=1', function () {
            $admin = adminUser();

            $this->actingAs($admin)->postJson(route('goods.store'), [
                'nombre' => 'Bien Tipo Cantidad',
                'tipo'   => 1,
            ]);

            $asset = Asset::where('name', 'Bien Tipo Cantidad')->first();
            expect($asset->type)->toBe('Cantidad');
        });

        it('el tipo se almacena como texto "Serial" al enviar tipo=2', function () {
            $admin = adminUser();

            $this->actingAs($admin)->postJson(route('goods.store'), [
                'nombre' => 'Bien Tipo Serial',
                'tipo'   => 2,
            ]);

            $asset = Asset::where('name', 'Bien Tipo Serial')->first();
            expect($asset->type)->toBe('Serial');
        });

        it('la respuesta incluye el id del bien creado', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('goods.store'), [
                    'nombre' => 'Bien Con ID',
                    'tipo'   => 1,
                ])
                ->assertStatus(200)
                ->assertJsonStructure(['success', 'message', 'assetId']);
        });

        it('el bien se crea sin imagen por defecto (image es null)', function () {
            $admin = adminUser();

            $this->actingAs($admin)->postJson(route('goods.store'), [
                'nombre' => 'Bien Sin Imagen',
                'tipo'   => 1,
            ]);

            $this->assertDatabaseHas('assets', [
                'name'  => 'Bien Sin Imagen',
                'image' => null,
            ]);
        });

        it('dos bienes con nombres distintos pueden coexistir', function () {
            $admin = adminUser();

            $this->actingAs($admin)->postJson(route('goods.store'), ['nombre' => 'Bien A', 'tipo' => 1]);
            $this->actingAs($admin)->postJson(route('goods.store'), ['nombre' => 'Bien B', 'tipo' => 2]);

            $this->assertDatabaseHas('assets', ['name' => 'Bien A']);
            $this->assertDatabaseHas('assets', ['name' => 'Bien B']);
            expect(Asset::count())->toBe(2);
        });
    });

    // ══════════════════════════════════════════════
    // EDITAR BIEN
    // ══════════════════════════════════════════════

    describe('Editar bien', function () {

        it('puede actualizar el nombre de un bien', function () {
            $admin = adminUser();
            $asset = crearBien(['name' => 'Nombre Original']);

            $this->actingAs($admin)
                ->postJson(route('goods.update'), [
                    'id'     => $asset->id,
                    'nombre' => 'Nombre Actualizado',
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('assets', [
                'id'   => $asset->id,
                'name' => 'Nombre Actualizado',
            ]);
        });

        it('el tipo no se modifica al editar (inmutabilidad del tipo)', function () {
            $admin = adminUser();
            $asset = crearBien(['type' => 'Cantidad']);

            // El controller solo actualiza nombre e imagen, no tipo
            $this->actingAs($admin)->postJson(route('goods.update'), [
                'id'     => $asset->id,
                'nombre' => $asset->name,
                'tipo'   => 2,
            ]);

            $this->assertDatabaseHas('assets', [
                'id'   => $asset->id,
                'type' => 'Cantidad',
            ]);
        });

        it('puede actualizar el nombre al mismo valor (excepción de unicidad)', function () {
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

        it('retorna 404 al intentar editar un bien inexistente', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.update'), [
                    'id'     => 99999,
                    'nombre' => 'No existe',
                ])
                ->assertStatus(404);
        });

        it('los datos del bien se actualizan correctamente en la base de datos', function () {
            $admin = adminUser();
            $asset = crearBien(['name' => 'Viejo Nombre']);

            $this->actingAs($admin)->postJson(route('goods.update'), [
                'id'     => $asset->id,
                'nombre' => 'Nuevo Nombre',
            ]);

            expect(Asset::find($asset->id)->name)->toBe('Nuevo Nombre');
        });
    });

    // ══════════════════════════════════════════════
    // ELIMINAR BIEN
    // ══════════════════════════════════════════════

    describe('Eliminar bien', function () {

        it('puede eliminar un bien que no tiene artículos', function () {
            $admin = adminUser();
            $asset = crearBien();

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        });

        it('NO puede eliminar un bien con artículos de tipo Cantidad (cantidad > 0)', function () {
            $admin = adminUser();
            $asset = crearBienConCantidad(5);

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(400)
                ->assertJson(['success' => false]);

            $this->assertDatabaseHas('assets', ['id' => $asset->id]);
        });

        it('NO puede eliminar un bien con artículos de tipo Serial', function () {
            $admin = adminUser();
            $asset = crearBienConSerial();

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(400)
                ->assertJson(['success' => false]);

            $this->assertDatabaseHas('assets', ['id' => $asset->id]);
        });

        it('la respuesta 400 incluye un mensaje de error descriptivo', function () {
            $admin = adminUser();
            $asset = crearBienConCantidad(3);

            $response = $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id));

            $response->assertStatus(400);
            expect($response->json('message'))->not->toBeEmpty();
        });

        it('retorna 404 al intentar eliminar un bien inexistente', function () {
            $this->actingAs(adminUser())
                ->deleteJson(route('goods.destroy', 99999))
                ->assertStatus(404);
        });

        it('eliminar un bien no afecta a los demás bienes', function () {
            $admin  = adminUser();
            $asset1 = crearBien(['name' => 'Bien que se queda']);
            $asset2 = crearBien(['name' => 'Bien que se borra']);

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset2->id))
                ->assertStatus(200);

            $this->assertDatabaseHas('assets', ['id' => $asset1->id]);
            $this->assertDatabaseMissing('assets', ['id' => $asset2->id]);
        });

        it('puede eliminar un bien vinculado a un inventario pero con cantidad 0', function () {
            $admin = adminUser();
            $asset = crearBien(['type' => 'Cantidad']);

            // Crear enlace sin registrar cantidad
            $group = Group::create(['name' => 'Grupo Vacío ' . uniqid()]);
            $inventory = Inventory::create([
                'name'                => 'Inventario Vacío',
                'responsible'         => 'Responsable',
                'conservation_status' => 'good',
                'group_id'            => $group->id,
            ]);
            AssetInventory::create([
                'asset_id'     => $asset->id,
                'inventory_id' => $inventory->id,
            ]);

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });

        it('puede eliminar un bien con inventario y cantidad explícita de 0', function () {
            $admin = adminUser();
            $asset = crearBien(['type' => 'Cantidad']);

            $group = Group::create(['name' => 'Grupo Cero ' . uniqid()]);
            $inventory = Inventory::create([
                'name'                => 'Inventario Cero',
                'responsible'         => 'Responsable',
                'conservation_status' => 'good',
                'group_id'            => $group->id,
            ]);
            $assetInventory = AssetInventory::create([
                'asset_id'     => $asset->id,
                'inventory_id' => $inventory->id,
            ]);
            AssetQuantity::create([
                'asset_inventory_id' => $assetInventory->id,
                'quantity'           => 0,
            ]);

            $this->actingAs($admin)
                ->deleteJson(route('goods.destroy', $asset->id))
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });

    // ══════════════════════════════════════════════
    // OBTENER BIENES EN JSON
    // ══════════════════════════════════════════════

    describe('Obtener bienes en formato JSON (GET /api/goods/get/json)', function () {

        it('devuelve una lista JSON de bienes', function () {
            $admin = adminUser();
            crearBien(['name' => 'Bien JSON 1']);
            crearBien(['name' => 'Bien JSON 2']);

            $this->actingAs($admin)
                ->getJson('/api/goods/get/json')
                ->assertStatus(200)
                ->assertJsonCount(2);
        });

        it('cada bien incluye los campos id, bien y tipo', function () {
            $admin = adminUser();
            crearBien(['name' => 'Bien Estructura']);

            $this->actingAs($admin)
                ->getJson('/api/goods/get/json')
                ->assertStatus(200)
                ->assertJsonStructure([
                    '*' => ['id', 'bien', 'tipo'],
                ]);
        });

        it('devuelve lista vacía cuando no hay bienes', function () {
            $this->actingAs(adminUser())
                ->getJson('/api/goods/get/json')
                ->assertStatus(200)
                ->assertJsonCount(0);
        });
    });
});
