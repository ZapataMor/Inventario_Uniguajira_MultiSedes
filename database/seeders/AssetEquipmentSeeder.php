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

        // 25 PCs en el inventario 37 (Networking Room)
        for ($i = 1; $i <= 25; $i++) {
            $equipments[] = [
                'asset_inventory_id' => 73,
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

        DB::table('asset_equipments')->insert($equipments);
    }
}
