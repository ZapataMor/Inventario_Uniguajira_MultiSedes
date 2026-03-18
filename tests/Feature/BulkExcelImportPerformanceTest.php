<?php

use App\Models\Asset;
use App\Models\AssetEquipment;
use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\Group;
use App\Models\Inventory;

describe('Carga masiva optimizada de Excel', function () {

    it('evita insertar seriales duplicados dentro del mismo lote global', function () {
        $group = Group::create(['name' => 'Grupo Global']);
        $inventory = Inventory::create([
            'name' => 'Laboratorio A',
            'responsible' => 'Coordinacion',
            'conservation_status' => 'good',
            'group_id' => $group->id,
        ]);

        $response = $this->actingAs(adminUser())
            ->postJson(route('goods.batchCreateGlobal'), [
                'rows' => [
                    [
                        'bien' => 'Portatil Global',
                        'tipo' => 'Serial',
                        'localizacion' => 'Laboratorio A',
                        'serial' => 'SER-100',
                    ],
                    [
                        'bien' => 'Portatil Global',
                        'tipo' => 'Serial',
                        'localizacion' => 'Laboratorio A',
                        'serial' => 'SER-100',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'created' => 2,
                'assigned' => 1,
            ]);

        expect($response->json('errors'))->toHaveCount(1);
        expect(AssetEquipment::where('serial', 'SER-100')->count())->toBe(1);
        expect(Asset::where('name', 'Portatil Global')->count())->toBe(1);
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
});
