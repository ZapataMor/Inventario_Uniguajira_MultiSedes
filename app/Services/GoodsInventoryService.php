<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\AssetEquipment;
use Illuminate\Support\Facades\DB;

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

    /**
     * Precarga los bienes por nombre y crea en un solo lote los faltantes.
     *
     * @param  array<string, string>  $assetDefinitions  [nombre => tipo]
     * @return array<string, \App\Models\Asset>
     */
    public function getOrCreateAssetsByName(array $assetDefinitions): array
    {
        if (empty($assetDefinitions)) {
            return [];
        }

        $assetNames = array_keys($assetDefinitions);

        $existing = Asset::query()
            ->whereIn('name', $assetNames)
            ->get(['id', 'name', 'type'])
            ->keyBy('name');

        $now = now();
        $missing = [];

        foreach ($assetDefinitions as $name => $type) {
            if (isset($existing[$name])) {
                continue;
            }

            $missing[] = [
                'name' => $name,
                'type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($missing)) {
            foreach (array_chunk($missing, 500) as $chunk) {
                Asset::query()->insertOrIgnore($chunk);
            }
        }

        return Asset::query()
            ->whereIn('name', $assetNames)
            ->get(['id', 'name', 'type'])
            ->keyBy('name')
            ->all();
    }

    /**
     * Garantiza las relaciones asset_inventory necesarias y devuelve su mapa.
     *
     * @param  array<int, array{inventory_id:int, asset_id:int}>  $pairs
     * @return array<string, int>
     */
    public function ensureAssetInventoryRelations(array $pairs): array
    {
        if (empty($pairs)) {
            return [];
        }

        $uniquePairs = [];
        foreach ($pairs as $pair) {
            $key = $this->pairKey($pair['inventory_id'], $pair['asset_id']);
            $uniquePairs[$key] = $pair;
        }

        $now = now();
        $rowsToInsert = array_map(function (array $pair) use ($now) {
            return [
                'inventory_id' => $pair['inventory_id'],
                'asset_id' => $pair['asset_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, array_values($uniquePairs));

        foreach (array_chunk($rowsToInsert, 500) as $chunk) {
            AssetInventory::query()->insertOrIgnore($chunk);
        }

        $inventoryIds = array_values(array_unique(array_column($uniquePairs, 'inventory_id')));
        $assetIds = array_values(array_unique(array_column($uniquePairs, 'asset_id')));

        $relations = AssetInventory::query()
            ->whereIn('inventory_id', $inventoryIds)
            ->whereIn('asset_id', $assetIds)
            ->get(['id', 'inventory_id', 'asset_id']);

        $map = [];
        foreach ($relations as $relation) {
            $map[$this->pairKey($relation->inventory_id, $relation->asset_id)] = $relation->id;
        }

        return $map;
    }

    /**
     * Devuelve lookup de seriales ya existentes.
     *
     * @param  array<int, string>  $serials
     * @return array<string, true>
     */
    public function getExistingSerialLookup(array $serials): array
    {
        $serials = array_values(array_unique(array_filter($serials)));

        if (empty($serials)) {
            return [];
        }

        return AssetEquipment::query()
            ->whereIn('serial', $serials)
            ->pluck('serial')
            ->mapWithKeys(fn ($serial) => [$this->serialKey($serial) => true])
            ->all();
    }

    /**
     * Suma cantidades por lote minimizando consultas.
     *
     * @param  array<int, int>  $incrementsByRelationId
     */
    public function applyQuantityIncrements(array $incrementsByRelationId): void
    {
        if (empty($incrementsByRelationId)) {
            return;
        }

        $relationIds = array_keys($incrementsByRelationId);

        $existing = AssetQuantity::query()
            ->whereIn('asset_inventory_id', $relationIds)
            ->get(['asset_inventory_id', 'quantity'])
            ->keyBy('asset_inventory_id');

        $now = now();
        $inserts = [];
        $updates = [];

        foreach ($incrementsByRelationId as $relationId => $increment) {
            if (isset($existing[$relationId])) {
                $updates[$relationId] = $existing[$relationId]->quantity + $increment;
                continue;
            }

            $inserts[] = [
                'asset_inventory_id' => $relationId,
                'quantity' => $increment,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                AssetQuantity::query()->insert($chunk);
            }
        }

        if (empty($updates)) {
            return;
        }

        $cases = [];
        $params = [];
        $ids = [];

        foreach ($updates as $relationId => $quantity) {
            $cases[] = 'WHEN ? THEN ?';
            $params[] = $relationId;
            $params[] = $quantity;
            $ids[] = $relationId;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params[] = $now;
        $params = array_merge($params, $ids);

        DB::update(
            'UPDATE asset_quantities SET quantity = CASE asset_inventory_id ' . implode(' ', $cases) . ' END, updated_at = ? WHERE asset_inventory_id IN (' . $placeholders . ')',
            $params
        );
    }

    /**
     * Inserta bienes serializados por lote.
     *
     * @param  array<int, array<string, mixed>>  $equipments
     */
    public function insertSerialEquipments(array $equipments): void
    {
        if (empty($equipments)) {
            return;
        }

        foreach (array_chunk($equipments, 500) as $chunk) {
            AssetEquipment::query()->insert($chunk);
        }
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

    public function serialKey(string $serial): string
    {
        return mb_strtolower(trim($serial));
    }

    private function pairKey(int $inventoryId, int $assetId): string
    {
        return $inventoryId . ':' . $assetId;
    }

}
