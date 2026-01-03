<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $departments = DB::table('departments')->pluck('id', 'name');

        DB::table('categories')->insert([
            [
                'name' => 'Acceso a mi cuenta',
                'department_id' => $departments['Atención y Ayuda'],
            ],
            [
                'name' => 'Clases y cursos',
                'department_id' => $departments['Control Escolar'],
            ],
            [
                'name' => 'Tareas y actividades',
                'department_id' => $departments['Control Escolar'],
            ],
            [
                'name' => 'Exámenes',
                'department_id' => $departments['Control Escolar'],
            ],
            [
                'name' => 'Archivos y materiales',
                'department_id' => $departments['Soporte Técnico'],
            ],
            [
                'name' => 'La página no funciona bien',
                'department_id' => $departments['Soporte Técnico'],
            ],
            [
                'name' => 'Contenido incorrecto',
                'department_id' => $departments['Control Escolar'],
            ],
            [
                'name' => 'Ayuda general',
                'department_id' => $departments['Atención y Ayuda'],
            ],
        ]);
    }
}
