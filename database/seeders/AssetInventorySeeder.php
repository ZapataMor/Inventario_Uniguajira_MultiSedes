<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [];

        /**
         * Aulas 1A–6D (inventory_id 1-36): bienes comunes por salón
         * - Escritorios (asset_id 1): muchos por salón
         * - Pizarras (asset_id 2): máximo 1-2 por salón
         * - Ventiladores (asset_id 3): varios por salón
         * - Escritorio Docente (asset_id 4): 1 por salón
         * - Lámparas (asset_id 5): máximo 1-2 por salón
         * - Puertas (asset_id 6): máximo 1-2 por salón
         * - Papeleras (asset_id 7): varias por salón
         */
        for ($inventory = 1; $inventory <= 36; $inventory++) {
            $records[] = ['asset_id' => 1, 'inventory_id' => $inventory]; // Escritorios
            $records[] = ['asset_id' => 2, 'inventory_id' => $inventory]; // Pizarras
            $records[] = ['asset_id' => 3, 'inventory_id' => $inventory]; // Ventiladores
            $records[] = ['asset_id' => 4, 'inventory_id' => $inventory]; // Escritorio Docente
            $records[] = ['asset_id' => 5, 'inventory_id' => $inventory]; // Lámparas
            $records[] = ['asset_id' => 6, 'inventory_id' => $inventory]; // Puertas
            $records[] = ['asset_id' => 7, 'inventory_id' => $inventory]; // Papeleras
        }

        /**
         * Networking Room (inventory_id 37): equipos de cómputo
         * - Computadores (asset_id 8): máximo 40
         * - Sillas (asset_id 9): varias
         * - Estantes (asset_id 10, 11): racks
         */
        $records[] = ['asset_id' => 8, 'inventory_id' => 37]; // Computadores
        $records[] = ['asset_id' => 9, 'inventory_id' => 37]; // Sillas
        $records[] = ['asset_id' => 10, 'inventory_id' => 37]; // Estante Grande
        $records[] = ['asset_id' => 11, 'inventory_id' => 37]; // Estante Mediano

        DB::table('asset_inventory')->insert($records);
    }
}
