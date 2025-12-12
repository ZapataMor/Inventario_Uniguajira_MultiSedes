<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetEquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipments = [];

        /**
         * Computadores en Networking Room (inventory_id 37, asset_id 8)
         * Máximo 40 computadores según la cantidad definida en AssetQuantitySeeder
         */
        $pivotId = DB::table('asset_inventory')
            ->where('asset_id', 8) // Computadores
            ->where('inventory_id', 37) // Networking Room
            ->value('id');

        if ($pivotId) {
            // Crear hasta 40 computadores serializados
            for ($i = 1; $i <= 40; $i++) {
                $equipments[] = [
                    'asset_inventory_id' => $pivotId,
                    'description' => 'Desktop computer',
                    'brand' => 'HP',
                    'model' => 'ProDesk 400 G6',
                    'serial' => 'SN' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'status' => 'activo',
                    'color' => 'Black',
                    'technical_conditions' => 'Good condition',
                    'entry_date' => now()->subDays(rand(10, 100)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('asset_equipments')->insert($equipments);
    }
}
