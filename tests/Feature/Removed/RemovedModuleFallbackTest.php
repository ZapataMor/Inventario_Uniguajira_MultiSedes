<?php

use App\Models\Asset;
use App\Models\Group;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('muestra el modulo de dados de baja aunque falte la tabla de seriales', function () {
    [$admin, $asset] = seedRemovedModuleFallbackData();

    Schema::dropIfExists('asset_equipments_removed');

    $response = $this->actingAs($admin)->get('/removed');

    $response->assertOk();
    $response->assertSee('Bienes Dados de Baja');
    $response->assertSee($asset->name);
    $response->assertSee('Prueba de fallback');
});

it('filtra los bienes dados de baja aunque falte la tabla de seriales', function () {
    [$admin, $asset] = seedRemovedModuleFallbackData();

    Schema::dropIfExists('asset_equipments_removed');

    $response = $this->actingAs($admin)->getJson('/api/removed/filter?type=all');

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'count' => 1,
    ]);
    $response->assertJsonPath('data.0.asset_name', $asset->name);
});

function seedRemovedModuleFallbackData(): array
{
    $admin = adminUser();

    $group = Group::create([
        'name' => 'Grupo fallback bajas',
    ]);

    $inventory = Inventory::create([
        'name' => 'Inventario fallback bajas',
        'responsible' => 'Responsable fallback',
        'conservation_status' => 'good',
        'group_id' => $group->id,
    ]);

    $asset = Asset::create([
        'name' => 'Bien cantidad fallback',
        'type' => 'Cantidad',
    ]);

    DB::table('assets_removed')->insert([
        'name' => $asset->name,
        'type' => 'Cantidad',
        'image' => null,
        'quantity' => 2,
        'reason' => 'Prueba de fallback',
        'asset_id' => $asset->id,
        'inventory_id' => $inventory->id,
        'user_id' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$admin, $asset];
}
