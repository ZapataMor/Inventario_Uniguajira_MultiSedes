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
});
