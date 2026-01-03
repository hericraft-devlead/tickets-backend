<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentsSeeder::class,
            CategoriesSeeder::class,
            PrioritiesSeeder::class,
            TagsSeeder::class,
            TicketStatusesSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
