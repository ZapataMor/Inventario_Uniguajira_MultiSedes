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

    public function deleteSerialGood(int $equipmentId): bool
    {
        // 1. Buscar equipo
        $equipment = AssetEquipment::find($equipmentId);

        if (!$equipment) {
            return false;
        }

        $relationId = $equipment->asset_inventory_id;

        // 2. Contar cuántos equipos están asociados a esa relación
        $totalEquipments = AssetEquipment::where('asset_inventory_id', $relationId)->count();

        // 3. Eliminar equipo
        if (!$equipment->delete()) {
            return false;
        }

        // 4. Si solo había uno, eliminar la relación asset_inventory
        if ($totalEquipments <= 1) {
            AssetInventory::where('id', $relationId)->delete();
        }

        return true;
    }


    public function updateSerial(int $id, array $details): bool
    {
        $equipment = AssetEquipment::find($id);

        if (!$equipment) {
            return false;
        }

        // Validar serial duplicado en otro equipo
        if (isset($details['serial'])) {

            $exists = AssetEquipment::where('serial', $details['serial'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe un bien con este número de serial.');
            }
        }

        // Actualizar campos
        $equipment->update([
            'description'          => $details['description'],
            'brand'                => $details['brand'],
            'model'                => $details['model'],
            'serial'               => $details['serial'],
            'status'               => $details['status'],
            'color'                => $details['color'],
            'technical_conditions' => $details['technical_conditions'],
            'entry_date'           => $details['entry_date'],
        ]);

        return true;
    }

}
