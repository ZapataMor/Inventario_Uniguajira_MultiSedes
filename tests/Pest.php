<?php

use App\Models\User;
use App\Models\Task;
use App\Models\Asset;
use App\Models\Group;
use App\Models\Inventory;
use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\AssetEquipment;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers globales de usuarios
|--------------------------------------------------------------------------
*/

/**
 * Crea y retorna un usuario con rol 'administrador'.
 */
function adminUser(): User
{
    return User::factory()->administrador()->create();
}

/**
 * Crea y retorna un usuario con rol 'consultor'.
 */
function consultorUser(): User
{
    return User::factory()->consultor()->create();
}

/*
|--------------------------------------------------------------------------
| Helpers globales de tareas
|--------------------------------------------------------------------------
*/

/**
 * Crea una tarea pendiente asociada al usuario dado.
 *
 * @param  array<string, mixed>  $overrides  Atributos opcionales para sobreescribir.
 */
function crearTareaPendiente(User $user, array $overrides = []): Task
{
    return Task::create(array_merge([
        'name'        => 'Tarea de prueba',
        'description' => 'Descripción de prueba',
        'date'        => now()->addDays(5)->toDateString(),
        'status'      => 'pending',
        'user_id'     => $user->id,
    ], $overrides));
}

/*
|--------------------------------------------------------------------------
| Helpers globales de bienes (assets)
|--------------------------------------------------------------------------
*/

/**
 * Crea un bien sin artículos (puede eliminarse).
 *
 * @param  array<string, mixed>  $overrides
 */
function crearBien(array $overrides = []): Asset
{
    static $counter = 0;
    $counter++;

    return Asset::create(array_merge([
        'name' => 'Bien de prueba ' . $counter,
        'type' => 'Cantidad',
    ], $overrides));
}

/**
 * Crea un bien de tipo Cantidad con artículos en un inventario
 * (cantidad > 0, NO puede eliminarse).
 */
function crearBienConCantidad(int $cantidad = 5, array $overrides = []): Asset
{
    static $counter = 0;
    $counter++;

    $asset = crearBien(array_merge(['type' => 'Cantidad'], $overrides));

    $group = Group::create(['name' => 'Grupo Test C' . $counter]);
    $inventory = Inventory::create([
        'name'                => 'Inventario Test C' . $counter,
        'responsible'         => 'Responsable Test',
        'conservation_status' => 'good',
        'group_id'            => $group->id,
    ]);
    $assetInventory = AssetInventory::create([
        'asset_id'     => $asset->id,
        'inventory_id' => $inventory->id,
    ]);
    AssetQuantity::create([
        'asset_inventory_id' => $assetInventory->id,
        'quantity'           => $cantidad,
    ]);

    return $asset;
}

/**
 * Crea un bien de tipo Serial con un equipo registrado
 * (count > 0, NO puede eliminarse).
 */
function crearBienConSerial(array $overrides = []): Asset
{
    static $counter = 0;
    $counter++;

    $asset = crearBien(array_merge(['type' => 'Serial'], $overrides));

    $group = Group::create(['name' => 'Grupo Test S' . $counter]);
    $inventory = Inventory::create([
        'name'                => 'Inventario Test S' . $counter,
        'responsible'         => 'Responsable Test',
        'conservation_status' => 'good',
        'group_id'            => $group->id,
    ]);
    $assetInventory = AssetInventory::create([
        'asset_id'     => $asset->id,
        'inventory_id' => $inventory->id,
    ]);
    AssetEquipment::create([
        'asset_inventory_id' => $assetInventory->id,
        'serial'             => 'SN-TEST-' . $counter . '-' . uniqid(),
        'status'             => 'activo',
    ]);

    return $asset;
}

/**
 * Crea una tarea completada asociada al usuario dado.
 *
 * @param  array<string, mixed>  $overrides  Atributos opcionales para sobreescribir.
 */
function crearTareaCompletada(User $user, array $overrides = []): Task
{
    return Task::create(array_merge([
        'name'        => 'Tarea completada',
        'description' => 'Descripción completada',
        'date'        => now()->addDays(5)->toDateString(),
        'status'      => 'completed',
        'user_id'     => $user->id,
    ], $overrides));
}
