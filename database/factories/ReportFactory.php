<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Realistic Indonesian school facility complaints.
     *
     * @var list<array{title: string, location: string, description: string}>
     */
    private const SAMPLES = [
        [
            'title' => 'Lampu ruang kelas mati',
            'location' => 'Ruang kelas, barisan lampu depan',
            'description' => 'Beberapa lampu mati sehingga ruangan gelap saat cuaca mendung.',
        ],
        [
            'title' => 'Kran air bocor',
            'location' => 'Toilet siswa, wastafel kedua',
            'description' => 'Kran terus menetes meskipun sudah ditutup rapat dan air tergenang di lantai.',
        ],
        [
            'title' => 'AC tidak dingin',
            'location' => 'Ruang kelas lantai satu',
            'description' => 'AC menyala tetapi tidak mengeluarkan udara dingin sejak beberapa hari terakhir.',
        ],
        [
            'title' => 'Meja belajar goyang',
            'location' => 'Ruang kelas, meja baris belakang',
            'description' => 'Baut penyangga meja hilang sehingga meja goyang saat digunakan.',
        ],
        [
            'title' => 'Proyektor tidak menampilkan gambar',
            'location' => 'Ruang kelas, langit-langit tengah',
            'description' => 'Proyektor menyala namun hanya menampilkan layar putih.',
        ],
        [
            'title' => 'Jaringan WiFi tidak dapat diakses',
            'location' => 'Perpustakaan, area baca',
            'description' => 'Jaringan tidak muncul pada daftar WiFi perangkat siswa.',
        ],
        [
            'title' => 'Pintu kelas tidak dapat dikunci',
            'location' => 'Ruang kelas, pintu utama',
            'description' => 'Gagang pintu longgar dan kunci tidak dapat diputar.',
        ],
        [
            'title' => 'Sampah menumpuk',
            'location' => 'Kantin sekolah, dekat tempat cuci tangan',
            'description' => 'Tempat sampah penuh dan belum diangkut sehingga sampah berserakan.',
        ],
        [
            'title' => 'Atap bocor saat hujan',
            'location' => 'Laboratorium, sisi timur',
            'description' => 'Air menetes dari langit-langit ketika hujan deras.',
        ],
        [
            'title' => 'Kursi rusak',
            'location' => 'Ruang guru, dekat jendela',
            'description' => 'Sandaran kursi patah sehingga tidak nyaman digunakan.',
        ],
    ];

    /** Cycled so a long test run never exhausts the sample list. */
    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $index = self::$sequence++;
        $sample = self::SAMPLES[$index % count(self::SAMPLES)];
        $suffix = $index >= count(self::SAMPLES) ? ' #'.(intdiv($index, count(self::SAMPLES)) + 1) : '';

        return [
            'identifier' => (string) Str::uuid7(),
            // These columns are enums, so only the real values are valid.
            'status' => fake()->randomElement(['Pending', 'Resolved', 'Rejected']),
            'severity' => fake()->randomElement(['Low', 'Medium', 'High', 'Critical']),
            'categories_id' => Category::factory(),
            'users_id' => User::factory(),
            'facilities_id' => Facility::factory(),
            'title' => $sample['title'].$suffix,
            'location_description' => $sample['location'],
            'description' => $sample['description'],
        ];
    }
}
