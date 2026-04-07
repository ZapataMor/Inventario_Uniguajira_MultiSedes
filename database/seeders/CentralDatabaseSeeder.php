<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CentralDatabaseSeeder extends Seeder
{
    /**
     * Seed data only for the central database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
