<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Report categories for a school facility reporting system.
 */
class CategorySeeder extends Seeder
{
    /** @var list<string> */
    public const CATEGORIES = [
        'Kerusakan Ringan',
        'Kerusakan Berat',
        'Kebersihan',
        'Kelistrikan',
        'Air dan Sanitasi',
        'Keamanan',
        'Furnitur',
        'Jaringan Internet',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
