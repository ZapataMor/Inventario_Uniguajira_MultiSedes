<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GroupSeeder::class,
            InventorySeeder::class,
            AssetSeeder::class,
            AssetInventorySeeder::class,
            AssetQuantitySeeder::class,
            AssetEquipmentSeeder::class,
            ReportFolderSeeder::class,
            TaskSeeder::class,
            // ActivityLogSeeder::class,
        ]);
    }
}
