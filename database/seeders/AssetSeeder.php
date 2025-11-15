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
            ['name' => 'Escritorios',      'type' => 'quantity', 'image' => 'seeders/goods/img_6805e94287b04.png'],
            ['name' => 'Pizarras',         'type' => 'quantity', 'image' => 'seeders/goods/img_67fb3abe51fcf.png'],
            ['name' => 'Ventiladores',     'type' => 'quantity', 'image' => 'seeders/goods/img_67fb35f2cc3e3.png'],
            ['name' => 'Escritorio Docente','type' => 'quantity', 'image' => 'seeders/goods/img_67fb350ce3c8d.png'],
            ['name' => 'Lámparas',         'type' => 'quantity', 'image' => 'seeders/goods/img_67fb39b659404.webp'],
            ['name' => 'Puertas',          'type' => 'quantity', 'image' => 'seeders/goods/img_67fb3a06c0295.jpg'],
            ['name' => 'Papelera',         'type' => 'quantity', 'image' => 'seeders/goods/img_67fb37ec5e0cd.png'],
            ['name' => 'Computadores',     'type' => 'serial',   'image' => 'seeders/goods/img_67fb377a73eaa.png'],
            ['name' => 'Sillas',           'type' => 'quantity', 'image' => 'seeders/goods/img_67fb3861cf13d.png'],
            ['name' => 'Estante Grande',   'type' => 'quantity', 'image' => 'seeders/goods/img_68215005cb13d.jpg'],
            ['name' => 'Estante Mediano',  'type' => 'quantity', 'image' => 'seeders/goods/img_682150295f31a.webp'],
            ['name' => 'VideoBeam',        'type' => 'serial',   'image' => 'seeders/goods/img_681eb9f740fb1.png'],
        ];

        DB::table('assets')->insert($assets);
    }
}
