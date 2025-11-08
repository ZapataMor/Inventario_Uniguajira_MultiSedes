<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            ['name' => 'Desks', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_6805e94287b04.png'],
            ['name' => 'Boards', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_67fb3abe51fcf.png'],
            ['name' => 'Ceiling Fans', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_67fb35f2cc3e3.png'],
            ['name' => 'Teacher Desk', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_67fb350ce3c8d.png'],
            ['name' => 'Lamps', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_67fb39b659404.webp'],
            ['name' => 'Doors', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_67fb3a06c0295.jpg'],
            ['name' => 'Trash Bins', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_67fb37ec5e0cd.png'],
            ['name' => 'Computers', 'type' => 'serial', 'image' => 'assets/uploads/img/goods/img_67fb377a73eaa.png'],
            ['name' => 'Chairs', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_67fb3861cf13d.png'],
            ['name' => 'Large Rack', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_68215005cb13d.jpg'],
            ['name' => 'Medium Rack', 'type' => 'quantity', 'image' => 'assets/uploads/img/goods/img_682150295f31a.webp'],
            ['name' => 'VideoBeam', 'type' => 'serial', 'image' => 'assets/uploads/img/goods/img_681eb9f740fb1.png'],
        ];

        DB::table('assets')->insert($assets);
    }
}
