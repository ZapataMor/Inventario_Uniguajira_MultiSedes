<?php

use App\Models\Asset;
use App\Models\AssetEquipment;
use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\Group;
use App\Models\Inventory;

describe('Carga masiva optimizada de Excel', function () {

    it('la ruta global crea bienes solo en catalogo y acepta tipo case-insensitive', function () {
        $response = $this->actingAs(adminUser())
            ->postJson(route('goods.batchCreateGlobal'), [
                'rows' => [
                    [
                        'bien' => 'Portatil Global',
                        'tipo' => 'serial',
                    ],
                    [
                        'bien' => 'Silla Global',
                        'tipo' => 'CANTIDAD',
                    ],
                    [
                        'bien' => 'Portatil Global',
                        'tipo' => 'Serial',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'created' => 2,
            ]);

        expect($response->json('errors'))->toHaveCount(1);
        expect(Asset::where('name', 'Portatil Global')->count())->toBe(1);
        expect(Asset::where('name', 'Portatil Global')->value('type'))->toBe('Serial');
        expect(Asset::where('name', 'Silla Global')->value('type'))->toBe('Cantidad');
        expect(AssetInventory::count())->toBe(0);
    });

    it('acumula cantidades del mismo bien en una sola relacion de inventario', function () {
        $group = Group::create(['name' => 'Grupo Inventario']);
        $inventory = Inventory::create([
            'name' => 'Salon B',
            'responsible' => 'Coordinacion',
            'conservation_status' => 'good',
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs(adminUser())
            ->postJson(route('goods-inventory.batchCreate', ['inventoryId' => $inventory->id]), [
                'rows' => [
                    [
                        'bien' => 'Silla Apilable',
                        'tipo' => 'Cantidad',
                        'cantidad' => 3,
                    ],
                    [
                        'bien' => 'Silla Apilable',
                        'tipo' => 'Cantidad',
                        'cantidad' => 4,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'created' => 2,
            ]);

        $asset = Asset::where('name', 'Silla Apilable')->firstOrFail();
        $relation = AssetInventory::where('asset_id', $asset->id)
            ->where('inventory_id', $inventory->id)
            ->firstOrFail();

        $quantity = AssetQuantity::where('asset_inventory_id', $relation->id)->value('quantity');

        expect($quantity)->toBe(7);
    });

    it('la carga por localizacion agrupa bienes que solo difieren en mayusculas', function () {
        $group = Group::create(['name' => 'Grupo Sede']);
        $inventory = Inventory::create([
            'name' => 'Desarrollo Humano',
            'responsible' => 'Coordinacion',
            'conservation_status' => 'good',
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs(adminUser())
            ->postJson(route('goods-inventory.batchCreateByLocalizacion'), [
                'rows' => [
                    [
                        'bien' => 'Monitor',
                        'tipo' => 'Serial',
                        'serial' => 'MON-001',
                        'localizacion' => 'DESARROLLO HUMANO',
                    ],
                    [
                        'bien' => 'monitor',
                        'tipo' => 'Serial',
                        'serial' => 'MON-002',
                        'localizacion' => 'Desarrollo Humano ',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'created' => 2,
                'errors' => [],
            ]);

        $assets = Asset::whereRaw('LOWER(name) = ?', ['monitor'])->get();
        expect($assets)->toHaveCount(1);

        $relation = AssetInventory::where('asset_id', $assets->first()->id)
            ->where('inventory_id', $inventory->id)
            ->firstOrFail();

        expect(AssetEquipment::where('asset_inventory_id', $relation->id)->count())->toBe(2);
    });

    it('la carga por localizacion rechaza todas las filas con un serial repetido en el archivo', function () {
        $group = Group::create(['name' => 'Grupo Duplicados']);
        Inventory::create([
            'name' => 'Sistemas',
            'responsible' => 'Coordinacion',
            'conservation_status' => 'good',
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs(adminUser())
            ->postJson(route('goods-inventory.batchCreateByLocalizacion'), [
                'rows' => [
                    ['bien' => 'Monitor', 'tipo' => 'Serial', 'serial' => 'DUP-1', 'localizacion' => 'Sistemas'],
                    ['bien' => 'Portatil', 'tipo' => 'Serial', 'serial' => 'OK-1', 'localizacion' => 'Sistemas'],
                    ['bien' => 'Monitor', 'tipo' => 'Serial', 'serial' => 'dup-1 ', 'localizacion' => 'Sistemas'],
                    ['bien' => 'Impresora', 'tipo' => 'Serial', 'serial' => '', 'localizacion' => 'Sistemas'],
                    ['bien' => 'Silla', 'tipo' => 'Cantidad', 'cantidad' => 2, 'localizacion' => 'No existe'],
                ],
            ]);

        $response->assertStatus(200)->assertJson(['created' => 1]);

        $failed = collect($response->json('failed_rows'))->keyBy('index');

        expect($failed->keys()->sort()->values()->all())->toBe([0, 2, 3, 4]);
        expect($failed[0])->toMatchArray(['type' => 'duplicate', 'fields' => ['serial'], 'group' => 'dup-1']);
        expect($failed[2])->toMatchArray(['type' => 'duplicate', 'group' => 'dup-1']);
        expect($failed[3])->toMatchArray(['type' => 'error', 'fields' => ['serial']]);
        expect($failed[4])->toMatchArray(['type' => 'error', 'fields' => ['localizacion']]);
        expect(AssetEquipment::where('serial', 'DUP-1')->exists())->toBeFalse();
    });

    it('la carga por localizacion guarda los detalles del serial y rechaza una fecha invalida', function () {
        $group = Group::create(['name' => 'Grupo Detalles']);
        Inventory::create([
            'name' => 'Biblioteca',
            'responsible' => 'Coordinacion',
            'conservation_status' => 'good',
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs(adminUser())
            ->postJson(route('goods-inventory.batchCreateByLocalizacion'), [
                'rows' => [
                    [
                        'bien' => 'Monitor', 'tipo' => 'Serial', 'serial' => 'DET-1', 'estado' => 'inactivo',
                        'descripcion' => 'Pantalla rayada', 'color' => 'NEGRO', 'condiciones' => 'Malo',
                        'fecha_ingreso' => '2024-01-15', 'localizacion' => 'Biblioteca',
                    ],
                    [
                        'bien' => 'Monitor', 'tipo' => 'Serial', 'serial' => 'DET-2',
                        'fecha_ingreso' => '31/02/2025', 'localizacion' => 'Biblioteca',
                    ],
                ],
            ]);

        $response->assertStatus(200)->assertJson(['created' => 1]);

        expect($response->json('failed_rows.0'))->toMatchArray([
            'index' => 1,
            'type' => 'error',
            'fields' => ['fecha_ingreso'],
        ]);

        $equipment = AssetEquipment::where('serial', 'DET-1')->firstOrFail();

        expect($equipment->description)->toBe('Pantalla rayada');
        expect($equipment->color)->toBe('NEGRO');
        expect($equipment->technical_conditions)->toBe('Malo');
        expect($equipment->status)->toBe('inactivo');
        expect((string) $equipment->entry_date)->toStartWith('2024-01-15');
        expect(AssetEquipment::where('serial', 'DET-2')->exists())->toBeFalse();
    });

    it('la carga en inventario informa donde esta registrado un serial existente', function () {
        $group = Group::create(['name' => 'Grupo Existente']);
        $inventory = Inventory::create([
            'name' => 'Bodega',
            'responsible' => 'Coordinacion',
            'conservation_status' => 'good',
            'group_id' => $group->id,
        ]);
        $route = route('goods-inventory.batchCreate', ['inventoryId' => $inventory->id]);
        $admin = adminUser();

        $this->actingAs($admin)
            ->postJson($route, ['rows' => [['bien' => 'Cpu', 'tipo' => 'Serial', 'serial' => 'OLD-1']]])
            ->assertJson(['created' => 1, 'failed_rows' => []]);

        $response = $this->actingAs($admin)
            ->postJson($route, ['rows' => [['bien' => 'Monitor', 'tipo' => 'Serial', 'serial' => 'OLD-1']]]);

        $response->assertStatus(200)->assertJson(['created' => 0]);

        expect($response->json('failed_rows.0'))->toMatchArray([
            'index' => 0,
            'type' => 'duplicate',
            'existing' => ['serial' => 'OLD-1', 'bien' => 'Cpu', 'inventario' => 'Bodega'],
        ]);
        expect(Asset::where('name', 'Monitor')->exists())->toBeFalse();
    });
});
