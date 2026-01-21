<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'admin@tickets.com',
            ],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'role' => 0, 
                'department_id' => null,
            ]
        );
        User::updateOrCreate(
            [
                'email' => 'cristianglz777@gmail.com',
            ],
            [
                'name' => 'Maverik',
                'password' => Hash::make('prueba123'),
                'role' => 1, 
                'department_id' => 3,
            ]
        );
        User::updateOrCreate(
            [
                'email' => 'zxcris02@gmail.com',
            ],
            [
                'name' => 'cris',
                'password' => Hash::make('prueba1234'),
                'role' => 0, 
                'department_id' => 3,
            ]
        );
    }
}
