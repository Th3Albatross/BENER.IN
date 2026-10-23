<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application with a usable school facility reporting dataset.
     *
     * Order matters: users and master data must exist before reports.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            FacilitySeeder::class,
            ReportSeeder::class,
        ]);
    }
}
