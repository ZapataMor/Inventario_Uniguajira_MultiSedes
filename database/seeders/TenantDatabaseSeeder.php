<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed data only for tenant databases.
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
