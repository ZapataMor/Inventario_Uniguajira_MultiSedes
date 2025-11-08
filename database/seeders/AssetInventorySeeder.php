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

        // Pupitres y tableros en los salones 1A–6D
        $id = 1;
        for ($inventory = 1; $inventory <= 36; $inventory++) {
            $records[] = ['asset_id' => 1, 'inventory_id' => $inventory]; // Desks
            $records[] = ['asset_id' => 2, 'inventory_id' => $inventory]; // Boards
            $id += 2;
        }

        // Networking Room (id 37) con computadores y racks
        $records[] = ['asset_id' => 8, 'inventory_id' => 37]; // Computers
        $records[] = ['asset_id' => 9, 'inventory_id' => 37]; // Chairs
        $records[] = ['asset_id' => 10, 'inventory_id' => 37]; // Large Rack
        $records[] = ['asset_id' => 11, 'inventory_id' => 37]; // Medium Rack

        DB::table('asset_inventory')->insert($records);
    }
}
