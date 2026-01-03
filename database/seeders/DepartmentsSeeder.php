<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('departments')->insert([
            ['name' => 'Soporte Técnico'],
            ['name' => 'Control Escolar'],
            ['name' => 'Atención y Ayuda'],
        ]);
    }
}
