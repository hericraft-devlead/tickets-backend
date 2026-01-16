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
                'email' => '123@tickets.com',
            ],
            [
                'name' => 'Maverik',
                'password' => Hash::make('prueba123'),
                'role' => 1, 
                'department_id' => 1,
            ]
        );
    }
}
