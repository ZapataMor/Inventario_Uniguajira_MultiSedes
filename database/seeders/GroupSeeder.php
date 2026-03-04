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
            ['name' => 'Bloque A'],
            ['name' => 'Bloque B'],
            ['name' => 'Bloque C'],
            ['name' => 'Bloque D'],
            ['name' => 'Salas'],
            ['name' => 'Bloque Administrativo'],
        ];

        DB::table('groups')->insert($groups);
    }
}
