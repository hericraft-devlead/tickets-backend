<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tags')->insert([
            ['name' => 'No puedo entrar'],
            ['name' => 'Olvidé mi contraseña'],
            ['name' => 'No aparece mi curso'],
            ['name' => 'No puedo enviar mi tarea'],
            ['name' => 'Se cerró mi examen'],
            ['name' => 'No abre el archivo'],
            ['name' => 'No carga la página'],
            ['name' => 'La página es lenta'],
            ['name' => 'Tengo una pregunta'],
            ['name' => 'Necesito ayuda'],
        ]);
    }
}
