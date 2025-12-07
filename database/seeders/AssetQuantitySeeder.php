<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetQuantitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $records = [];

        // Generar cantidades aleatorias para los primeros 72 registros (aulas)
        for ($i = 1; $i <= 72; $i++) {
            $records[] = [
                'asset_inventory_id' => $i,
                'quantity' => rand(30, 40), // Reverted key to 'quantity'
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Networking Room y racks (IDs 37, 38, 39)
        $extraRecords = [
            [
                'asset_inventory_id' => 37, // Updated to valid ID
                'quantity' => 25,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'asset_inventory_id' => 37, // Updated to valid ID
                'quantity' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'asset_inventory_id' => 37, // Updated to valid ID
                'quantity' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('asset_quantities')->insert(array_merge($records, $extraRecords));
    }
}
