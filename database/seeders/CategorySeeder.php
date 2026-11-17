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
        'Perabot Kelas',
        'Peralatan Pembelajaran',
        'Perangkat Elektronik',
        'Perlengkapan Ruangan',
        'Bangunan dan Struktur',
        'Fasilitas Laboratorium dan Olahraga',
        'Fasilitas Area Sekolah',
        'Fasilitas Sanitasi dan Air'
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
