<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrioritiesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('priorities')->insert([
            [
                'name' => 'Baja',
                'level' => 1,
            ],
            [
                'name' => 'Normal',
                'level' => 2,
            ],
            [
                'name' => 'Urgente',
                'level' => 3,
            ],
            [
                'name' => 'Muy urgente',
                'level' => 4,
            ],
        ]);
    }
}
