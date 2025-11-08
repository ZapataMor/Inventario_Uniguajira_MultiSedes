<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            ['name' => 'Review general inventory', 'description' => 'Check all inventories and generate a report.', 'date' => '2025-04-01', 'status' => 'pending', 'user_id' => 1],
            ['name' => 'Update assets', 'description' => 'Update asset information.', 'date' => '2025-04-02', 'status' => 'pending', 'user_id' => 1],
            ['name' => 'Monthly report', 'description' => 'Generate and export monthly inventory report.', 'date' => '2025-04-03', 'status' => 'pending', 'user_id' => 1],
        ];

        DB::table('tasks')->insert($tasks);
    }
}
