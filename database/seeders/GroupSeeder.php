<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Block A'],
            ['name' => 'Block B'],
            ['name' => 'Block C'],
            ['name' => 'Block D'],
            ['name' => 'Rooms'],
            ['name' => 'Administrative Block'],
        ];

        DB::table('groups')->insert($groups);
    }
}
