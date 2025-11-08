<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventories = [];
        $now = now();

        // Bloques A, B, C, D (10 aulas por bloque)
        foreach (['A', 'B', 'C', 'D'] as $index => $letter) {
            $groupId = $index + 1;
            for ($i = 1; $i <= 10; $i++) {
                $inventories[] = [
                    'name' => "{$i}{$letter}",
                    'group_id' => $groupId,
                    'responsible' => null,
                    'conservation_status' => 'good',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Rooms (grupo 5)
        $inventories[] = [
            'name' => 'Networking Room',
            'group_id' => 5,
            'responsible' => null,
            'conservation_status' => 'good',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $inventories[] = [
            'name' => 'Audiovisual 1',
            'group_id' => 5,
            'responsible' => null,
            'conservation_status' => 'good',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $inventories[] = [
            'name' => 'Audiovisual 2',
            'group_id' => 5,
            'responsible' => null,
            'conservation_status' => 'good',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Administrative Block (grupo 6)
        $inventories[] = [
            'name' => 'Office',
            'group_id' => 6,
            'responsible' => 'Administrator',
            'conservation_status' => 'good',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('inventories')->insert($inventories);
    }
}
