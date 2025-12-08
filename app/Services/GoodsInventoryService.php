<?php

namespace App\Services;

use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\AssetEquipment;

class GoodsInventoryService
{
    /**
     * Añadir cantidad a un inventario (asset_quantities)
     */
    public function addQuantity(int $inventoryId, int $assetId, int $quantity)
    {
        // 1. Buscar o crear la relación pivot asset_inventory
        $relation = AssetInventory::firstOrCreate(
            [
                'asset_id'     => $assetId,
                'inventory_id' => $inventoryId
            ]
        );

        // 2. Sumar cantidad si existe, o crearla
        $quantityRecord = AssetQuantity::firstOrNew([
            'asset_inventory_id' => $relation->id
        ]);

        $quantityRecord->quantity = ($quantityRecord->exists)
            ? $quantityRecord->quantity + $quantity
            : $quantity;

        $quantityRecord->save();

        return $relation->id;
    }

    /**
     * Añadir bien serializado (asset_equipments)
     */
    public function addSerial(int $inventoryId, int $assetId, array $details)
    {
        // 1. Buscar o crear relación pivot
        $relation = AssetInventory::firstOrCreate(
            [
                'asset_id'     => $assetId,
                'inventory_id' => $inventoryId
            ]
        );

        // 2. Validar serial único
        if (AssetEquipment::where('serial', $details['serial'])->exists()) {
            return false;
        }

        // 3. Crear el equipo
        $equipment = AssetEquipment::create([
            'asset_inventory_id'    => $relation->id,
            'description'           => $details['description'] ?? null,
            'brand'                 => $details['brand'] ?? null,
            'model'                 => $details['model'] ?? null,
            'serial'                => $details['serial'],
            'status'                => $details['state'] ?? 'active',
            'color'                 => $details['color'] ?? null,
            'technical_conditions'  => $details['technical_conditions'] ?? null,
            'entry_date'            => $details['entry_date'] ?? now()->toDateString(),
        ]);

        return $equipment->id;
    }
}
