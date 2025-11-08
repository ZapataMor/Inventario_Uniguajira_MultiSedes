<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folders = [
            ['name' => 'Reports 2025 - 1'],
            ['name' => 'Reports 2024 - 2'],
            ['name' => 'Reports 2024 - 1'],
            ['name' => 'Reports 2023 - 2'],
            ['name' => 'Reports 2023 - 1'],
            ['name' => 'Reports 2022 - 2'],
            ['name' => 'Reports 2022 - 1'],
        ];

        DB::table('report_folders')->insert($folders);
    }
}
