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

        /**
         * 1) Aulas 1A–6D (inventory_id 1-36): cantidades realistas por tipo de bien
         */
        $classroomQuantities = [
            1 => fn() => rand(30, 40),      // Escritorios: muchos por salón
            2 => fn() => rand(1, 2),        // Pizarras: máximo 1-2 por salón
            3 => fn() => rand(2, 4),         // Ventiladores: varios por salón
            4 => fn() => 1,                 // Escritorio Docente: 1 por salón
            5 => fn() => rand(1, 2),        // Lámparas: máximo 1-2 por salón
            6 => fn() => rand(1, 2),        // Puertas: máximo 1-2 por salón
            7 => fn() => rand(2, 3),        // Papeleras: varias por salón
        ];

        foreach (range(1, 36) as $inventoryId) {
            foreach ($classroomQuantities as $assetId => $quantityFn) {
                $pivotId = DB::table('asset_inventory')
                    ->where('asset_id', $assetId)
                    ->where('inventory_id', $inventoryId)
                    ->value('id');

                if (! $pivotId) {
                    continue;
                }

                $records[] = [
                    'asset_inventory_id' => $pivotId,
                    'quantity' => $quantityFn(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        /**
         * 2) Networking Room (inventory_id 37): equipos de cómputo
         *    NOTA: Los computadores (asset_id 8) son tipo "Serial", 
         *    por lo que NO deben tener cantidad en asset_quantities,
         *    solo equipos serializados en asset_equipments.
         */
        $networkingQuantities = [
            // 8 => NO crear cantidad (es tipo Serial, se maneja con asset_equipments)
            9 => 40,  // Sillas: una por computador (tipo Cantidad)
            10 => 1,  // Estante Grande: 1 (tipo Cantidad)
            11 => 1,  // Estante Mediano: 1 (tipo Cantidad)
        ];

        foreach ($networkingQuantities as $assetId => $quantity) {
            $pivotId = DB::table('asset_inventory')
                ->where('asset_id', $assetId)
                ->where('inventory_id', 37)
                ->value('id');

            if (! $pivotId) {
                continue;
            }

            $records[] = [
                'asset_inventory_id' => $pivotId,
                'quantity' => $quantity,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('asset_quantities')->insert($records);
    }
}
