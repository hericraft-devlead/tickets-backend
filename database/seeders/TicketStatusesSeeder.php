<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketStatusesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ticket_statuses')->insert([
            [
                'name' => 'Abierto',
                'is_final' => false,
            ],
            [
                'name' => 'En proceso',
                'is_final' => false,
            ],
            [
                'name' => 'Resuelto',
                'is_final' => false,
            ],
            [
                'name' => 'Cerrado',
                'is_final' => true,
            ],
        ]);
    }
}
