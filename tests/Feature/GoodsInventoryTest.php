<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Inventory;
use App\Models\Asset;

class GoodsInventoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_rejects_negative_quantity_for_cantidad_type()
    {
        $inventory = Inventory::factory()->create();
        $asset = Asset::factory()->create();

        $response = $this->postJson('/api/goods-inventory/create', [
            'inventarioId' => $inventory->id,
            'bien_id' => $asset->id,
            'bien_tipo' => 1, // Tipo cantidad
            'cantidad' => -5,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Cantidad inválida.',
        ]);
    }

    /** @test */
    public function it_creates_a_good_with_valid_quantity()
    {
        $inventory = Inventory::factory()->create();
        $asset = Asset::factory()->create();

        $response = $this->postJson('/api/goods-inventory/create', [
            'inventarioId' => $inventory->id,
            'bien_id' => $asset->id,
            'bien_tipo' => 1, // Tipo cantidad
            'cantidad' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Bien agregado exitosamente.',
        ]);
    }

    /** @test */
    public function it_rejects_missing_serial_for_serial_type()
    {
        $inventory = Inventory::factory()->create();
        $asset = Asset::factory()->create();

        $response = $this->postJson('/api/goods-inventory/create', [
            'inventarioId' => $inventory->id,
            'bien_id' => $asset->id,
            'bien_tipo' => 2, // Tipo serial
            // Falta el campo serial
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'El campo serial es obligatorio.',
        ]);
    }

    /** @test */
    public function it_creates_a_good_with_valid_serial()
    {
        $inventory = Inventory::factory()->create();
        $asset = Asset::factory()->create();

        $response = $this->postJson('/api/goods-inventory/create', [
            'inventarioId' => $inventory->id,
            'bien_id' => $asset->id,
            'bien_tipo' => 2, // Tipo serial
            'serial' => 'ABC123',
            'descripcion' => 'Descripción del bien',
            'marca' => 'Marca X',
            'modelo' => 'Modelo Y',
            'estado' => 'activo',
            'color' => 'Rojo',
            'condiciones_tecnicas' => 'Óptimas',
            'fecha_ingreso' => '2025-12-08',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Bien agregado exitosamente.',
        ]);
    }
}
