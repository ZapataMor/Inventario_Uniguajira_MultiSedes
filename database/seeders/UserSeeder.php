<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Luis', 'username' => 'luis', 'email' => 'luis@email.com', 'password' => Hash::make('1234'), 'role' => 'admin'],
            ['name' => 'Renzo', 'username' => 'renzo', 'email' => 'renzo@email.com', 'password' => Hash::make('1234'), 'role' => 'admin'],
            ['name' => 'Kevin', 'username' => 'kevin', 'email' => 'kevin@email.com', 'password' => Hash::make('1234'), 'role' => 'admin'],
            ['name' => 'Consultor', 'username' => 'consultor', 'email' => 'consultor@email.com', 'password' => Hash::make('consul'), 'role' => 'consultant'],
            ['name' => 'Consultora', 'username' => 'consultora', 'email' => 'consultora@email.com', 'password' => Hash::make('consul'), 'role' => 'consultant'],
            ['name' => 'Daniel', 'username' => 'Danie1l6', 'email' => 'daniel@email.com', 'password' => Hash::make('1234'), 'role' => 'admin'],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
